<?php

use App\Models\Capital;
use App\Models\CapitalWeather;
use App\Models\Country;

it('shows the country with its capitals in name order', function () {
    $country = Country::factory()
        ->has(Capital::factory()->state(['name' => 'Pretoria']))
        ->has(Capital::factory()->state(['name' => 'Cape Town']))
        ->create();

    $this->get(route('countries.show', $country))
        ->assertOk()
        ->assertSee($country->name_common)
        ->assertSeeInOrder(['Cape Town', 'Pretoria']);
});

it('returns 404 for a missing or non-numeric country id', function (string $url) {
    $this->get($url)->assertNotFound();
})->with([
    'missing id' => ['/countries/999999'],
    'non-numeric id' => ['/countries/abc'],
]);

it('shows "Weather unavailable" only for the capital without weather', function () {
    $country = Country::factory()
        ->has(Capital::factory()->state(['name' => 'Pretoria']))
        ->has(
            Capital::factory()
                ->state(['name' => 'Cape Town'])
                ->has(CapitalWeather::factory(), 'weather')
        )
        ->create();

    $response = $this->get(route('countries.show', $country));

    $response->assertOk()
        ->assertSeeInOrder(['Cape Town', 'Temperature', 'Pretoria', 'Weather unavailable']);

    expect(substr_count($response->getContent(), 'Weather unavailable'))->toBe(1);
});

it('displays only known memberships', function () {
    $country = Country::factory()->create(['memberships' => ['nato', 'future_org']]);

    $this->get(route('countries.show', $country))
        ->assertOk()
        ->assertSee('NATO')
        ->assertDontSee('future_org');
});

it('hides rows for empty fields', function () {
    $country = Country::factory()->create([
        'memberships' => [],
        'cca3' => null,
        'region' => null,
        'continents' => null,
        'population' => null,
        'subregion' => null,
        'area_km2' => null,
        'sovereign' => null,
        'name_official' => null,
        'descriptions_short' => null,
        'flag_url' => null,
    ]);

    $this->get(route('countries.show', $country))
        ->assertOk()
        ->assertDontSee('Sovereign')
        ->assertDontSee('Area (')
        ->assertDontSee('Subregion')
        ->assertDontSee('Population')
        ->assertDontSee('Continents')
        ->assertDontSee('Region')
        ->assertDontSee('CCA3')
        ->assertDontSee('Memberships')
        ->assertDontSee('Density')
        ->assertDontSee('Flag of')
        ->assertDontSee('Language(s)')
        ->assertDontSee('()');
});

it('displays country with format numbers for population', function () {
    $country = Country::factory()->create([
        'population' => 2335930,
    ]);

    $this->get(route('countries.show', $country))
        ->assertOk()
        ->assertSee('2,335,930');
});

it('displays country with format numbers for area', function (float $area, string $expected) {
    $country = Country::factory()->create([
        'area_km2' => $area,
        'population' => 882,
    ]);

    $this->get(route('countries.show', $country))
        ->assertOk()
        ->assertSee($expected);
})->with([
    'number with 2 zero' => [28748.00, '28,748'],
    'float number' => [0.44, '0.44'],
]);

it('shows sovereign status', function (bool $sovereign, string $expected) {
    $country = Country::factory()->create(['sovereign' => $sovereign]);

    $html = $this->get(route('countries.show', $country))
        ->assertOk()
        ->getContent();

    expect($html)->toMatch("#Sovereign</dt>\s*<dd[^>]*>\s*{$expected}\s*</dd>#");
})->with([
    'sovereign' => [true, 'Yes'],
    'not sovereign' => [false, 'No'],
]);

it('shows Previous and Next by name order', function () {
    Country::factory()->create([
        'name_common' => 'Alpha',
    ]);

    Country::factory()->create([
        'name_common' => 'Charlie',
    ]);

    $country = Country::factory()->create([
        'name_common' => 'Bravo',
    ]);

    $this->get(route('countries.show', $country))
        ->assertOk()
        ->assertSeeInOrder(['Previous', 'Alpha', 'Next', 'Charlie']);
});

it('shows only Next on the first country', function () {
    $country = Country::factory()->create([
        'name_common' => 'Alpha',
    ]);

    Country::factory()->create([
        'name_common' => 'Charlie',
    ]);

    Country::factory()->create([
        'name_common' => 'Bravo',
    ]);

    $this->get(route('countries.show', $country))
        ->assertOk()
        ->assertSeeInOrder(['Next', 'Bravo'])
        ->assertDontSee('Previous');
});

it('shows only Previous on the last country', function () {
    Country::factory()->create([
        'name_common' => 'Alpha',
    ]);

    $country = Country::factory()->create([
        'name_common' => 'Charlie',
    ]);

    Country::factory()->create([
        'name_common' => 'Bravo',
    ]);

    $this->get(route('countries.show', $country))
        ->assertOk()
        ->assertSeeInOrder(['Previous', 'Bravo'])
        ->assertDontSee('Next');
});

it('shows fresh weather without the outdated warning', function () {
    $this->freezeSecond();

    $country = Country::factory()
        ->has(
            Capital::factory()->has(
                CapitalWeather::factory()->state(['observed_at' => now()->subMinutes(25)]),
                'weather'
            )
        )
        ->create();

    $this->get(route('countries.show', $country))
        ->assertOk()
        ->assertSee('25 minutes ago')
        ->assertDontSee('may be outdated');
});

it('shows outdated weather with the outdated warning', function () {
    $this->freezeSecond();

    $country = Country::factory()
        ->has(
            Capital::factory()->has(
                CapitalWeather::factory()->stale(), 'weather'
            )
        )
        ->create();

    $this->get(route('countries.show', $country))
        ->assertOk()
        ->assertSee((CapitalWeather::STALE_AFTER_HOURS + 1).' hours ago')
        ->assertSee('may be outdated');
});
