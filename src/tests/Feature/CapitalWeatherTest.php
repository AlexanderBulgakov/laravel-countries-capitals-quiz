<?php

use App\Enums\WeatherCode;
use App\Models\Capital;
use App\Models\CapitalWeather;

it('casts weather_code to the WeatherCode enum when loaded through Capital::weather()', function () {
    $capital = Capital::factory()
        ->has(CapitalWeather::factory()->state(['weather_code' => WeatherCode::Fog]), 'weather')
        ->create();

    expect($capital->weather->weather_code)->toBe(WeatherCode::Fog);
});

it('marks weather as stale after the threshold', function (int $minutes, bool $expected) {
    $this->freezeSecond();

    $weather = CapitalWeather::factory()
        ->create(['observed_at' => now()->subMinutes($minutes)]);

    expect($weather->fresh()->isStale())->toBe($expected);
})->with([
    'just before threshold' => [CapitalWeather::STALE_AFTER_HOURS * 60 - 1, false],
    'exactly at threshold' => [CapitalWeather::STALE_AFTER_HOURS * 60, false],
    'just after threshold' => [CapitalWeather::STALE_AFTER_HOURS * 60 + 1, true],
]);

it('creates stale weather with the stale factory state', function () {
    $weather = CapitalWeather::factory()->stale()->create();

    expect($weather->fresh()->isStale())->toBeTrue();
});
