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
