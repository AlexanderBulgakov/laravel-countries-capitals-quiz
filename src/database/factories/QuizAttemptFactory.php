<?php

namespace Database\Factories;

use App\Enums\QuizMode;
use App\Enums\QuizOutcome;
use App\Models\QuizAttempt;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<QuizAttempt>
 */
class QuizAttemptFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $outcome = fake()->randomElement(QuizOutcome::cases());

        if ($outcome === QuizOutcome::Completed) {
            $score = 10;
        } else {
            $score = fake()->numberBetween(1, 5);
        }

        return [
            'user_id' => User::factory(),
            'mode' => fake()->randomElement(QuizMode::cases()),
            'outcome' => $outcome,
            'score' => $score,
            'stopped_at_question' => $outcome === QuizOutcome::Completed ? 10 : $score + 1,
        ];
    }

    public function capitalToCountry(): static
    {
        return $this->state(fn (array $attributes) => [
            'mode' => QuizMode::CapitalToCountry,
        ]);
    }

    public function countryToCapital(): static
    {
        return $this->state(fn (array $attributes) => [
            'mode' => QuizMode::CountryToCapital,
        ]);
    }

    public function completed(): static
    {
        return $this->state(fn (array $attributes) => [
            'outcome' => QuizOutcome::Completed,
            'score' => 10,
            'stopped_at_question' => 10,
        ]);
    }

    public function failed(): static
    {
        return $this->state(fn (array $attributes) => [
            'outcome' => QuizOutcome::Failed,
            'score' => 1,
            'stopped_at_question' => 2,
        ]);
    }

    public function timeout(): static
    {
        return $this->state(fn (array $attributes) => [
            'outcome' => QuizOutcome::Timeout,
            'score' => 1,
            'stopped_at_question' => 2,
        ]);
    }
}
