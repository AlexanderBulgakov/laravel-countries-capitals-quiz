<?php

namespace Database\Factories;

use App\Models\Country;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Country>
 */
class CountryFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'restcountries_uuid' => fake()->uuid(),
            'cca3' => strtoupper(fake()->unique()->lexify('???')),
            'name_common' => fake()->unique()->country(),
            'name_official' => fake()->country(),
            'region' => fake()->randomElement(['Africa', 'Americas', 'Asia', 'Europe', 'Oceania']),
            'continents' => [fake()->randomElement(['Africa', 'Americas', 'Asia', 'Europe', 'Oceania'])],
            'descriptions_short' => fake()->sentence(),
            'flag_url' => fake()->imageUrl(),
        ];
    }
}
