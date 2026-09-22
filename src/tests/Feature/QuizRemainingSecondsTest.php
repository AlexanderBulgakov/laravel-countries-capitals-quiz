<?php

use App\Enums\QuizMode;
use App\Models\Capital;
use App\Models\Country;
use App\Services\Quiz\GuestQuizSession;

it('returns a positive remaining time right after starting', function () {
    Country::factory()
        ->count(4)
        ->has(Capital::factory())
        ->create(['region' => 'Europe']);

    $quizSession = app(GuestQuizSession::class);
    $quizSession->start(QuizMode::CountryToCapital);

    expect($quizSession->remainingSeconds())->toBeGreaterThan(0);
});

it('never returns a negative remaining time once the duration has passed', function () {
    Country::factory()
        ->count(4)
        ->has(Capital::factory())
        ->create(['region' => 'Europe']);

    $quizSession = app(GuestQuizSession::class);
    $quizSession->start(QuizMode::CountryToCapital);

    session(['quiz.pending.question_started_at' => now()->subMinutes(5)->timestamp]);

    expect($quizSession->remainingSeconds())->toBe(0);
});
