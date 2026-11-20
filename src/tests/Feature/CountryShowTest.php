<?php

use App\Models\Country;
use App\Models\Capital;

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
