<?php

use App\Models\Country;

it('lists countries on the countries page', function () {
    Country::factory()->create(['name_common' => 'Testlandia']);

    $response = $this->get('/countries');

    $response->assertOk();
    $response->assertSee('Testlandia');
});

it('filters countries by region', function () {
    Country::factory()->create(['region' => 'Africa', 'name_common' => 'Alpha Country']);
    Country::factory()->create(['region' => 'Europe', 'name_common' => 'Beta Country']);

    $response = $this->get('/countries?region=Africa');

    $response->assertOk();
    $response->assertSee('Alpha Country');
    $response->assertDontSee('Beta Country');
});
