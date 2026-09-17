<?php

use App\Models\Country;

afterEach(function () {
    // Same pattern as CountriesSyncTest — LIKE catches both '__TEST_A__' and
    // '__TEST_B__' markers used below, regardless of shared dev DB contents.
    Country::where('region', 'LIKE', '__TEST%')->delete();
});

it('lists countries on the countries page', function () {
    Country::factory()->create(['region' => '__TEST_A__', 'name_common' => 'Testlandia']);

    $response = $this->get('/countries?region=__TEST_A__');

    $response->assertOk();
    $response->assertSee('Testlandia');
});

it('filters countries by region', function () {
    Country::factory()->create(['region' => '__TEST_A__', 'name_common' => 'Alpha Country']);
    Country::factory()->create(['region' => '__TEST_B__', 'name_common' => 'Beta Country']);

    $response = $this->get('/countries?region=__TEST_A__');

    $response->assertOk();
    $response->assertSee('Alpha Country');
    $response->assertDontSee('Beta Country');
});

