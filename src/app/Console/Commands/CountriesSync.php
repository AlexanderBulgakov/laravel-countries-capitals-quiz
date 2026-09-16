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
    private const PAGE_LIMIT = 100;

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $offset = 0;
        $totalSite = null;
        $created = 0;
        $updated = 0;

        do {
            try {
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
