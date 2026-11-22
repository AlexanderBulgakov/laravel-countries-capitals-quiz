<?php

namespace Database\Factories;

use App\Enums\WeatherCode;
use App\Models\Capital;
use App\Models\CapitalWeather;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<CapitalWeather>
 */
class CapitalWeatherFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $now = now();

        return [
            'capital_id' => Capital::factory(),
            'temperature_c' => fake()->randomFloat(1, -30, 40),
            'wind_speed_kmh' => fake()->randomFloat(1, 0, 100),
            'weather_code' => fake()->randomElement(WeatherCode::cases()),
            'is_day' => fake()->boolean(),
            'observed_at' => $now,
            'updated_at' => $now,
        ];
    }

    public function stale(): static
    {
        return $this->state(function () {
            $observedAt = now()->subHours(CapitalWeather::STALE_AFTER_HOURS + 1);

            return ['observed_at' => $observedAt, 'updated_at' => $observedAt];
        });
    }
}
