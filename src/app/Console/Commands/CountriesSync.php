<?php

namespace App\Console\Commands;

use App\Models\Capital;
use App\Models\Country;
use Illuminate\Console\Command;
use Illuminate\Http\Client\RequestException;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;

#[Signature('countries:sync')]
#[Description('Sync countries and capitals from the REST Countries API')]
class CountriesSync extends Command
{
    // Maximum page size on the REST Countries free tier (100; paid plans allow up to 500) —
    // using the max keeps the number of requests (and rate-limit exposure) as low as possible.
    private const PAGE_LIMIT = 100;

    /**
     * Fetches countries page by page (REST Countries v5 paginates all responses)
     * and upserts them. Aborts on the first failed page rather than skipping it,
     * so a partial API outage never leaves the database in a half-synced state —
     * whatever was already committed on prior pages stays untouched.
     */
    public function handle(): int
    {
        $offset = 0;
        $totalSite = null;
        $created = 0;
        $updated = 0;

        do {
            try {
                // retry() alone only retries connection-level failures; throw() converts
                // non-2xx responses into exceptions too, so retry() catches those as well.
                $response = Http::withToken(config('services.restcountries.key'))
                    ->retry(3, 500)
                    ->throw()
                    ->get(config('services.restcountries.base_url'), [
                        'limit' => self::PAGE_LIMIT,
                        'offset' => $offset,
                        'response_fields' => 'names.common,names.official,codes,region,uuid,continents,descriptions.short,flag.url_svg,capitals'
                    ]);
            } catch (RequestException $e) {
                Log::error('countries:sync: failed to fetch page', [
                    'offset' => $offset,
                    'message' => $e->getMessage(),
                ]);

                $this->error("Failed at offset {$offset}, stopping. Existing data is untouched.");

                return self::FAILURE;
            }

            $totalSite ??= $response->json('data.meta.total');

            foreach ($response->json('data.objects', []) as $countryData) {
                $country = $this->syncCountry($countryData);

                $country->wasRecentlyCreated ? $created++ : $updated++;
            }

            $more = $response->json('data.meta.more', false);
            $offset += self::PAGE_LIMIT;
        } while ($more);

        $this->info("Synced: {$created} created, {$updated} updated.");

        $totalDb = Country::count();

        if ($totalDb === $totalSite) {
            $this->info("Total countries in database: {$totalDb} (matches API total).");
        } else {
            $this->warn("Mismatch: database has {$totalDb}, but the API reports {$totalSite}.");
        }

        return self::SUCCESS;
    }

    /**
     * Matched by restcountries_uuid, not cca3 (ISO alpha-3 code): the latter
     * isn't guaranteed non-empty/unique across all API entries, while uuid is
     * REST Countries' own internal identifier — always present, guaranteed
     * unique by construction.
     */
    private function syncCountry(array $data): Country
    {
        $country = Country::updateOrCreate(
            ['restcountries_uuid' => $data['uuid']],
            [
                'cca3' => $data['codes']['alpha_3'] ?? null,
                'name_common' => $data['names']['common'],
                'name_official' => $data['names']['official'] ?? null,
                'region' => $data['region'] ?? null,
                'continents' => $data['continents'] ?? null,
                'descriptions_short' => $data['descriptions']['short'] ?? null,
                'flag_url' => $data['flag']['url_svg'] ?? null,
            ]
        );

        foreach ($data['capitals'] ?? [] as $capitalData) {
            Capital::updateOrCreate(
                ['country_id' => $country->id, 'name' => $capitalData['name']],
            );
        }

        return $country;
    }
}
