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
            'population' => fake()->numberBetween(1_000, 1_500_000_000),
            'subregion' => fake()->randomElement(['Western Asia', 'Southern Asia', 'Southeast Europe', 'Northern Africa', 'Polynesia', 'Middle Africa']),
            'area_km2' => fake()->randomFloat(2, 100, 1_000_000),
            'sovereign' => fake()->boolean(80),
            'memberships' => [],
        ];
    }
}
