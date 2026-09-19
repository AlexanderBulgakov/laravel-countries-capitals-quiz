<?php

use App\Enums\QuizMode;
use App\Models\Capital;
use App\Models\Country;
use App\Services\Quiz\QuestionGenerator;

it('returns four unique option ids including the target', function () {
    Country::factory()
        ->count(4)
        ->has(Capital::factory())
        ->create(['region' => 'Europe']);

    $question = app(QuestionGenerator::class)->generate(QuizMode::CountryToCapital);

    expect($question->optionCountryIds)->toHaveCount(4);
    expect(array_unique($question->optionCountryIds))->toHaveCount(4);
    expect($question->optionCountryIds)->toContain($question->targetCountryId);
});

it('never selects a country without a capital', function () {
    Country::factory()
        ->count(4)
        ->has(Capital::factory())
        ->create(['region' => 'Europe']);

    $withoutCapital = Country::factory()->create(['region' => 'Europe']);

    foreach (range(1, 20) as $_) {
        $question = app(QuestionGenerator::class)->generate(QuizMode::CountryToCapital);

        expect($question->optionCountryIds)->not->toContain($withoutCapital->id);
    }
});

it('tops up distractors from other regions when the same region runs short', function () {
    Country::factory()
        ->count(1)
        ->has(Capital::factory())
        ->create(['region' => '__TEST_SMALL__']);

    Country::factory()
        ->count(3)
        ->has(Capital::factory())
        ->create(['region' => 'Europe']);

    foreach (range(1, 20) as $_) {
        $question = app(QuestionGenerator::class)->generate(QuizMode::CountryToCapital);

        expect($question->optionCountryIds)->toHaveCount(4);
    }
});

it('excludes given country ids from being picked again', function () {
    Country::factory()
        ->count(6)
        ->has(Capital::factory())
        ->create(['region' => 'Europe']);

    $excludedIds = Country::query()->inRandomOrder()->limit(2)->pluck('id')->all();

    foreach (range(1, 20) as $_) {
        $question = app(QuestionGenerator::class)->generate(QuizMode::CountryToCapital, $excludedIds);

        expect(array_intersect($question->optionCountryIds, $excludedIds))->toBeEmpty();
    }
});

it('carries the requested mode through to the generated question', function () {
    Country::factory()
        ->count(4)
        ->has(Capital::factory())
        ->create(['region' => 'Europe']);

    $question = app(QuestionGenerator::class)->generate(QuizMode::CapitalToCountry);

    expect($question->mode)->toBe(QuizMode::CapitalToCountry);
});
