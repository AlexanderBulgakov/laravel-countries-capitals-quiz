<?php

namespace App\Jobs;

use App\Enums\WeatherCode;
use App\Models\Capital;
use App\Models\CapitalWeather;
use Illuminate\Bus\Batchable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class SyncCapitalWeatherJob implements ShouldQueue
{
    use Batchable, Queueable;

    public int $tries = 3;

    /** @var list<int> */
    public array $backoff = [60, 300];

    /**
     * Create a new job instance.
     *
     * @param  list<int>  $capitalIds
     */
    public function __construct(
        public array $capitalIds
    ) {}

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        $capitals = Capital::whereKey($this->capitalIds)
            ->whereNotNull('lat')->whereNotNull('lng')
            ->orderBy('id')
            ->get(['id', 'lat', 'lng']);

        if ($capitals->isEmpty()) {
            return;
        }

        $response = Http::timeout(10)->throw()->get(config('services.open_meteo.base_url'), [
            'latitude' => $capitals->pluck('lat')->implode(','),
            'longitude' => $capitals->pluck('lng')->implode(','),
            'current' => 'temperature_2m,weather_code,wind_speed_10m,is_day',
        ]);

        $data = $response->json();
        $data = array_is_list($data) ? $data : [$data];

        if (count($data) !== $capitals->count()) {
            $this->fail('The number of capitals does not match the number in the data.');

            return;
        }

        $rows = [];
        foreach ($capitals as $i => $capital) {
            $current = $data[$i]['current'] ?? null;
            $rawCode = $current['weather_code'] ?? null;
            $code = is_int($rawCode) ? WeatherCode::tryFrom($rawCode) : null;

            if ($code === null || ! isset($current['temperature_2m'], $current['wind_speed_10m'], $current['is_day'], $current['time'])) {
                Log::warning('weather:sync: invalid weather data skipped', [
                    'capital_id' => $capital->id,
                    'current' => $current,
                ]);

                continue;
            }

            $rows[] = [
                'capital_id' => $capital->id,
                'temperature_c' => $current['temperature_2m'],
                'wind_speed_kmh' => $current['wind_speed_10m'],
                'weather_code' => $code,
                'is_day' => (bool) $current['is_day'],
                'observed_at' => Carbon::createFromFormat('Y-m-d\TH:i', $current['time'], 'UTC'),
            ];
        }

        if (! empty($rows)) {
            CapitalWeather::upsert($rows, uniqueBy: ['capital_id'], update: [
                'temperature_c', 'wind_speed_kmh', 'weather_code', 'is_day', 'observed_at',
            ]);
        }
    }
}
