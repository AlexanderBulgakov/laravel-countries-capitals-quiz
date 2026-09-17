<?php

use App\Models\Capital;
use App\Models\Country;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;

it('syncs countries and their capitals from the API', function () {
    $uuid = (string) Str::uuid();

    Http::fake([
        'api.restcountries.com/*' => Http::response([
            'data' => [
                'objects' => [[
                    'uuid' => $uuid,
                    'codes' => ['alpha_3' => 'ZAF'],
                    'names' => [
                        'common' => 'South Africa',
                        'official' => 'Republic of South Africa',
                    ],
                    'region' => 'Africa',
                    'continents' => ['Africa'],
                    'descriptions' => [
                        'short' => 'A republic at the southern tip of Africa.',
                    ],
                    'flag' => ['url_svg' => 'https://example.test/flag.svg'],
                    'capitals' => [
                        ['name' => 'Pretoria'],
                        ['name' => 'Cape Town'],
                    ],
                ]],
                'meta' => ['total' => 1, 'count' => 1, 'limit' => 100, 'offset' => 0, 'more' => false],
            ],
        ], 200),
    ]);

    $this->artisan('countries:sync')->assertExitCode(0);

    $country = Country::where('restcountries_uuid', $uuid)->first();

    expect($country)->not->toBeNull();
    expect($country->cca3)->toBe('ZAF');
    expect($country->capitals)->toHaveCount(2);
    expect($country->capitals->pluck('name'))->toContain('Pretoria', 'Cape Town');
});

it('is idempotent — running sync twice updates instead of duplicating', function () {
    $uuid = (string) Str::uuid();

    $fixture = fn (string $commonName) => [
        'data' => [
            'objects' => [
                [
                    'uuid' => $uuid,
                    'codes' => ['alpha_3' => 'ZAF'],
                    'names' => [
                        'common' => $commonName,
                        'official' => 'Republic of South Africa',
                    ],
                    'region' => 'Africa',
                    'continents' => ['Africa'],
                    'descriptions' => ['short' => 'A republic at the southern tip of Africa.'],
                    'flag' => ['url_svg' => 'https://example.test/flag.svg'],
                    'capitals' => [
                        ['name' => 'Pretoria'],
                        ['name' => 'Cape Town'],
                    ],
                ],
            ],
            'meta' => ['total' => 1, 'count' => 1, 'limit' => 100, 'offset' => 0, 'more' => false],
        ],
    ];

    Http::fake([
        'api.restcountries.com/*' => Http::sequence()
            ->push($fixture('South Africa'), 200)
            ->push($fixture('South Africa (updated)'), 200),
    ]);

    $this->artisan('countries:sync');
    $this->artisan('countries:sync');

    $country = Country::where('restcountries_uuid', $uuid)->first();

    expect(Country::where('restcountries_uuid', $uuid)->count())->toBe(1);
    expect(Capital::where('country_id', $country->id)->count())->toBe(2);
    expect($country->name_common)->toBe('South Africa (updated)');
});
