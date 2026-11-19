<?php

namespace App\Jobs;

use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Http\Client\RequestException;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use RuntimeException;
use Illuminate\Bus\Batchable;

class SyncCapitalWeatherJob implements ShouldQueue
{
    use Queueable, Batchable;

    /**
     * Create a new job instance.
     * @param list<int> $capitalIds
     */
    public function __construct(
        public array $capitalIds
    ) {}

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        // array_is_list($json) ? $json : [$json]

        // try {
        //     $response = Http::get(config('services.forecast.base_url'), [
        //         'latitude' => '48.85,51.51',
        //         'longitude' => '2.35,-0.13',
        //         'current' => 'temperature_2m,weather_code,wind_speed_10m,is_day',
        //     ]);
        // } catch (RequestException $e) {
        //     Log::error('forecast: failed to fetch page', [
        //         'message' => $e->getMessage(),
        //     ]);

        //     throw new RuntimeException('failed to fetch page');
        // }
    }
}
