<?php

use App\Models\Capital;
use App\Models\Country;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;

afterEach(function () {
    // Guarantees test fixtures are cleaned up even if an assertion fails —
    // 'Antarctica' is used as a marker value that never occurs among real
    // REST Countries data, so it's safe to delete by this field alone.
    Country::where('region', 'Antarctica')->delete();
});

it('syncs countries and their capitals from the API', function () {
    $uuid = (string) Str::uuid();

    Http::fake([
        'api.restcountries.com/*' => Http::response([
            'data' => [
                'objects' => [[
                    'uuid' => $uuid,
                    'codes' => ['alpha_3' => 'ZZZ'],
                    'names' => [
                        'common' => 'Test Country',
                        'official' => 'Test Country Official',
                    ],
                    'region' => 'Antarctica',
                    'continents' => ['Antarctica'],
                    'descriptions' => [
                        'short' => 'A fixture, not a real country.',
                    ],
                    'flag' => ['url_svg' => 'https://example.test/flag.svg'],
                    'capitals' => [
                        ['name' => 'Testopolis'],
                        ['name' => 'Testopolis2'],
                    ],
                ]],
                'meta' => ['total' => 1, 'count' => 1, 'limit' => 100, 'offset' => 0, 'more' => false],
            ],
        ], 200),
    ]);

    $this->artisan('countries:sync')->assertExitCode(0);

    $country = Country::where('restcountries_uuid', $uuid)->first();

    expect($country)->not->toBeNull();
    expect($country->cca3)->toBe('ZZZ');
    expect($country->capitals)->toHaveCount(2);
    expect($country->capitals->pluck('name'))->toContain('Testopolis', 'Testopolis2');
});

it('is idempotent — running sync twice updates instead of duplicating', function () {
    $uuid = (string) Str::uuid();

    $fixture = fn (string $commonName) => [
        'data' => [
            'objects' => [
                [
                    'uuid' => $uuid,
                    'codes' => ['alpha_3' => 'ZZZ'],
                    'names' => [
                        'common' => $commonName,
                        'official' => 'Test Country Official',
                    ],
                    'region' => 'Antarctica',
                    'continents' => ['Antarctica'],
                    'descriptions' => ['short' => 'A fixture, not a real country.'],
                    'flag' => ['url_svg' => 'https://example.test/flag.svg'],
                    'capitals' => [
                        ['name' => 'Testopolis'],
                        ['name' => 'Testopolis2'],
                    ],
                ],
            ],
            'meta' => ['total' => 1, 'count' => 1, 'limit' => 100, 'offset' => 0, 'more' => false],
        ],
    ];

    Http::fake([
        'api.restcountries.com/*' => Http::sequence()
            ->push($fixture('Test Country'), 200)
            ->push($fixture('Test Country (updated)'), 200),
    ]);

    $this->artisan('countries:sync');
    $this->artisan('countries:sync');

    $country = Country::where('restcountries_uuid', $uuid)->first();

    expect(Country::where('restcountries_uuid', $uuid)->count())->toBe(1);
    expect(Capital::where('country_id', $country->id)->count())->toBe(2);
    expect($country->name_common)->toBe('Test Country (updated)');
});
