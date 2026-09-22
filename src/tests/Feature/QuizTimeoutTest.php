<?php

use App\Enums\QuizMode;
use App\Enums\QuizOutcome;
use App\Models\Capital;
use App\Models\Country;
use App\Services\Quiz\GuestQuizSession;

it('ends the attempt as a timeout when the time limit has passed', function () {
    Country::factory()
        ->count(4)
        ->has(Capital::factory())
        ->create(['region' => 'Europe']);

    $quizSession = app(GuestQuizSession::class);
    $quizSession->start(QuizMode::CountryToCapital);

    session(['quiz.pending.question_started_at' => now()->subMinutes(5)->timestamp]);

    $result = $quizSession->submitAnswer(null);

    expect($result->finished)->toBeTrue();
    expect($result->outcome)->toBe(QuizOutcome::Timeout);
});
