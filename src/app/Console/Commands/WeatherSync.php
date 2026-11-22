<?php

namespace App\Console\Commands;

use App\Jobs\SyncCapitalWeatherJob;
use App\Models\Capital;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Bus;

#[Signature('weather:sync')]
#[Description('Sync weather for capitals from the Open-Meteo API')]
class WeatherSync extends Command
{
    private const CHUNK_SIZE = 50;

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $jobs = Capital::whereNotNull('lat')->whereNotNull('lng')
            ->orderBy('id')
            ->pluck('id')
            ->chunk(self::CHUNK_SIZE)
            ->map(
                fn ($ids) => new SyncCapitalWeatherJob($ids->values()->all())
            );

        if ($jobs->isEmpty()) {
            $this->warn('No capitals with coordinates - run countries:sync first.');

            return self::FAILURE;
        }

        $batch = Bus::batch($jobs)->name('weather:sync')->allowFailures()->dispatch();

        $this->info("Dispatched {$jobs->count()} jobs (batch {$batch->id}).");

        return self::SUCCESS;
    }
}
