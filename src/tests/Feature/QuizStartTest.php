<?php

use App\Enums\QuizMode;
use App\Models\Capital;
use App\Models\Country;

it('resumes the same question on repeated visits (simulating a page refresh)', function () {
    Country::factory()
        ->count(4)
        ->has(Capital::factory())
        ->create(['region' => 'Europe']);

    $this->get(route('quiz.start', QuizMode::CountryToCapital))->assertOk();

    $firstPending = session('quiz.pending');

    $this->get(route('quiz.start', QuizMode::CountryToCapital))->assertOk();

    expect(session('quiz.pending'))->toEqual($firstPending);
});

it('starts a fresh question when switching quiz modes mid-session', function () {
    Country::factory()
        ->count(4)
        ->has(Capital::factory())
        ->create(['region' => 'Europe']);

    $this->get(route('quiz.start', QuizMode::CountryToCapital))->assertOk();

    $this->get(route('quiz.start', QuizMode::CapitalToCountry))->assertOk();

    expect(session('quiz.mode'))->toBe(QuizMode::CapitalToCountry->value);
});
