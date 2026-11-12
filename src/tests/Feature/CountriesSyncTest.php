<?php

use App\Models\Capital;
use App\Models\Country;
use App\Models\Language;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

function countryFixture(
    string $uuid,
    string $name,
    string $cca3,
    array $languages = []
): array {
    return [
        'names' => [
            'common' => $name,
            'official' => 'Republic of Albania',
        ],
        'codes' => ['alpha_3' => $cca3],
        'capitals' => [
            ['name' => 'Tirana'],
        ],
        'flag' => ['url_svg' => 'https://flags.restcountries.com/v5/svg/al.svg'],
        'region' => 'Europe',
        'subregion' => 'Southeast Europe',
        'area' => ['kilometers' => 28748],
        'classification' => ['sovereign' => true],
        'continents' => ['Europe'],
        'descriptions' => ['short' => 'Albania is a parliamentary republic in the western Balkans, on the Adriatic and Ionian seas.'],
        'languages' => $languages,
        'memberships' => [
            'african_union' => false,
            'arab_league' => false,
            'asean' => false,
            'brics' => false,
            'commonwealth' => false,
            'eu' => false,
            'eurozone' => false,
            'g20' => false,
            'g7' => false,
            'nato' => true,
            'oecd' => false,
            'opec' => false,
            'schengen' => false,
            'un' => true,
        ],
        'population' => 2335930,
        'uuid' => $uuid,
    ];
}

function apiPage(array ...$countries): array
{
    return [
        'data' => [
            'objects' => $countries,
            'meta' => ['total' => count($countries), 'count' => count($countries), 'limit' => 100, 'offset' => 0, 'more' => false],
        ],
    ];
}

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

it('syncs filter fields: subregion, area, density, sovereign and memberships', function () {
    $uuid = (string) Str::uuid();

    Http::fake([
        'api.restcountries.com/*' => Http::response(
            apiPage(
                countryFixture(
                    $uuid,
                    'Albania',
                    'ALB',
                    [
                        [
                            'iso639_3' => 'sqi',
                            'name' => 'Albanian',
                        ],
                    ]
                ),
            )
        ),
    ]);

    $this->artisan('countries:sync')->assertExitCode(0);

    $country = Country::where('restcountries_uuid', $uuid)->first();

    expect($country->memberships)->toBe(['nato', 'un']);
    expect($country->population)->toBe(2335930);
    expect($country->area_km2)->toBe('28748.00');
    expect($country->density)->toBe('81.26');
    expect($country->subregion)->toBe('Southeast Europe');
    expect($country->sovereign)->toBeTrue();
    expect($country->languages->pluck('iso639_3')->all())->toBe(['sqi']);
});

it('shares one language row between countries that speak it', function () {
    $uuid = (string) Str::uuid();
    $uuid2 = (string) Str::uuid();

    Http::fake([
        'api.restcountries.com/*' => Http::response(
            apiPage(
                countryFixture(
                    $uuid,
                    'Albania',
                    'ALB',
                    [
                        [
                            'iso639_3' => 'sqi',
                            'name' => 'Albanian',
                        ],
                    ]
                ),
                countryFixture(
                    $uuid2,
                    'Country 2',
                    'COU',
                    [
                        [
                            'iso639_3' => 'eng',
                            'name' => 'English',
                        ],
                        [
                            'iso639_3' => 'sqi',
                            'name' => 'Albanian',
                        ],
                        [
                            'iso639_3' => 'por',
                            'name' => 'Portuguese',
                        ],
                    ]
                ),
            )
        ),
    ]);

    $this->artisan('countries:sync')->assertExitCode(0);

    $languages = Language::all();

    $albania = Country::firstWhere('restcountries_uuid', $uuid);
    expect($albania->languages->pluck('name')->all())
        ->toEqualCanonicalizing(['Albanian']);

    $country2 = Country::firstWhere('restcountries_uuid', $uuid2);
    expect($country2->languages->pluck('name')->all())
        ->toEqualCanonicalizing(['Albanian', 'English', 'Portuguese']);

    expect($languages->pluck('name')->all())->toEqualCanonicalizing(['Albanian', 'English', 'Portuguese']);
});

it('skips a language without iso639_3 and logs a warning', function () {
    $uuid = (string) Str::uuid();

    Http::fake([
        'api.restcountries.com/*' => Http::response(
            apiPage(
                countryFixture(
                    $uuid,
                    'Albania',
                    'ALB',
                    [
                        [
                            'iso639_3' => 'sqi',
                            'name' => 'Albanian',
                        ],
                        [
                            'iso639_3' => '',
                            'name' => 'Test',
                        ],
                    ]
                )
            )
        ),
    ]);

    Log::spy();

    $this->artisan('countries:sync')->assertExitCode(0);

    Log::shouldHaveReceived('warning')
        ->once()
        ->with('countries:sync: language without iso639_3 skipped', [
            'country' => 'Albania',
            'language' => 'Test',
        ]);

    $albania = Country::firstWhere('restcountries_uuid', $uuid);
    expect($albania->languages->pluck('iso639_3')->all())->toBe(['sqi']);
    expect(Language::count())->toBe(1);
});

it('detaches a language the API no longer lists for a country', function () {
    $uuid = (string) Str::uuid();

    $data1 = apiPage(
        countryFixture(
            $uuid,
            'Albania',
            'ALB',
            [
                [
                    'iso639_3' => 'sqi',
                    'name' => 'Albanian',
                ],
                [
                    'iso639_3' => 'eng',
                    'name' => 'English',
                ],
            ]
        )
    );

    $data2 = apiPage(
        countryFixture(
            $uuid,
            'Albania',
            'ALB',
            [
                [
                    'iso639_3' => 'sqi',
                    'name' => 'Albanian',
                ],
            ]
        )
    );

    Http::fake([
        'api.restcountries.com/*' => Http::sequence()
            ->push($data1, 200)
            ->push($data2, 200),
    ]);

    $this->artisan('countries:sync')->assertExitCode(0);
    $this->artisan('countries:sync')->assertExitCode(0);

    $languages = Language::all();

    expect($languages->pluck('name')->all())->toEqualCanonicalizing(['Albanian', 'English']);
    expect($languages)->toHaveCount(2);

    $albania = Country::firstWhere('restcountries_uuid', $uuid);
    expect($albania->languages->pluck('name')->all())
        ->toEqualCanonicalizing(['Albanian']);
});
