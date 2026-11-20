<?php

use App\Enums\WeatherCode;
use App\Jobs\SyncCapitalWeatherJob;
use App\Models\Capital;
use App\Models\CapitalWeather;
use Illuminate\Http\Client\RequestException;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

function openMeteoLocation(array $current = [], array $without = []): array
{
    $defaults = [
        'time' => '2026-11-17T22:00',
        'interval' => 900,
        'temperature_2m' => 13.2,
        'weather_code' => 2,
        'wind_speed_10m' => 19.6,
        'is_day' => 0,
    ];

    return [
        'latitude' => 48.853382,
        'longitude' => 2.355208,
        'generationtime_ms' => 0.09012222290039062,
        'utc_offset_seconds' => 0,
        'timezone' => 'GMT',
        'timezone_abbreviation' => 'GMT',
        'elevation' => 46.0,
        'current_units' => [
            'time' => 'iso8601',
            'interval' => 'seconds',
            'temperature_2m' => '°C',
            'weather_code' => 'wmo code',
            'wind_speed_10m' => 'km/h',
            'is_day' => '',
        ],
        'current' => Arr::except([...$defaults, ...$current], $without),
    ];
}

it('stores weather for each capital from a multi-location response', function () {
    $capital1 = Capital::factory()->create(['lat' => 48.85, 'lng' => 2.35]);
    $capital2 = Capital::factory()->create(['lat' => 51.51, 'lng' => -0.13]);

    Http::fake([
        'api.open-meteo.com/*' => Http::response([
            openMeteoLocation(['is_day' => 1]),
            openMeteoLocation(),
        ], 200),
    ]);

    (new SyncCapitalWeatherJob([$capital1->id, $capital2->id]))->handle();

    Http::assertSent(fn ($request) => $request['latitude'] === '48.850000,51.510000'
        && $request['longitude'] === '2.350000,-0.130000'
    );

    $weather1 = CapitalWeather::where('capital_id', $capital1->id)->first();
    $weather2 = CapitalWeather::where('capital_id', $capital2->id)->first();

    expect(CapitalWeather::count())->toBe(2);

    expect($weather1->temperature_c)->toBe('13.2');
    expect($weather1->wind_speed_kmh)->toBe('19.6');
    expect($weather1->weather_code)->toBe(WeatherCode::PartlyCloudy);
    expect($weather1->observed_at->toDateTimeString())->toBe('2026-11-17 22:00:00');
    expect($weather1->is_day)->toBeTrue();

    expect($weather2->is_day)->toBeFalse();
});

it('handles a single-location response returned as an object', function () {
    $capital = Capital::factory()->create();

    Http::fake([
        'api.open-meteo.com/*' => Http::response(
            openMeteoLocation(),
            200
        ),
    ]);

    (new SyncCapitalWeatherJob([$capital->id]))->handle();

    expect(CapitalWeather::count())->toBe(1);
    expect(CapitalWeather::first()->capital_id)->toBe($capital->id);
});

it('skips a location with invalid weather data and keeps the others', function (array $current, array $without = []) {
    $capital1 = Capital::factory()->create(['lat' => 48.85, 'lng' => 2.35]);
    $capital2 = Capital::factory()->create(['lat' => 51.51, 'lng' => -0.13]);

    Http::fake([
        'api.open-meteo.com/*' => Http::response([
            openMeteoLocation($current, $without),
            openMeteoLocation(),
        ], 200),
    ]);

    Log::spy();

    (new SyncCapitalWeatherJob([$capital1->id, $capital2->id]))->handle();

    Log::shouldHaveReceived('warning')->once()->withArgs(
        fn ($message, $context) => $message === 'weather:sync: invalid weather data skipped'
            && $context['capital_id'] === $capital1->id
    );

    $weather1 = CapitalWeather::where('capital_id', $capital1->id)->first();
    $weather2 = CapitalWeather::where('capital_id', $capital2->id)->first();

    expect(CapitalWeather::count())->toBe(1);
    expect($weather1)->toBeNull();
    expect($weather2)->not->toBeNull();
})->with([
    'unknown weather_code' => [['weather_code' => 42]],
    'non-numeric weather_code' => [['weather_code' => 'abc']],
    'missing temperature_2m' => [[], ['temperature_2m']],
    'null temperature_2m' => [['temperature_2m' => null]],
    'is_day null' => [['is_day' => null]],
]);

it('rethrows on API error and leaves stored weather untouched', function () {
    $capital = Capital::factory()->create();

    $weather = CapitalWeather::factory()->create([
        'capital_id' => $capital->id,
        'updated_at' => now()->subHours(3),
    ]);

    Http::fake([
        'api.open-meteo.com/*' => Http::response(
            [
                'error' => 'Internal Server Error',
            ], 500
        ),
    ]);

    expect(fn () => (new SyncCapitalWeatherJob([$capital->id]))->handle())
        ->toThrow(RequestException::class);

    Http::assertSentCount(1);

    expect($weather->fresh()->updated_at->equalTo($weather->updated_at))->toBeTrue();
});

it('updates weather on re-sync instead of creating a duplicate', function () {
    $capital = Capital::factory()->create();

    $apiFirst = openMeteoLocation();
    $apiUpdated = openMeteoLocation([
        'time' => '2026-11-17T23:00',
        'temperature_2m' => 10.0,
        'weather_code' => 3,
        'wind_speed_10m' => 5.5,
        'is_day' => 1,
    ]);

    Http::fake([
        'api.open-meteo.com/*' => Http::sequence()
            ->push($apiFirst, 200)
            ->push($apiUpdated, 200),
    ]);

    $this->freezeSecond();
    (new SyncCapitalWeatherJob([$capital->id]))->handle();
    $firstSyncAt = now();

    $this->travel(1)->hours();
    (new SyncCapitalWeatherJob([$capital->id]))->handle();

    $weather = CapitalWeather::firstWhere('capital_id', $capital->id);

    expect(CapitalWeather::count())->toBe(1);
    expect($weather->observed_at->toDateTimeString())->toBe('2026-11-17 23:00:00');
    expect($weather->temperature_c)->toBe('10.0');
    expect($weather->weather_code)->toBe(WeatherCode::Overcast);
    expect($weather->wind_speed_kmh)->toBe('5.5');
    expect($weather->is_day)->toBeTrue();
    expect($weather->created_at->equalTo($firstSyncAt))->toBeTrue();
    expect($weather->updated_at->equalTo(now()))->toBeTrue();
});
