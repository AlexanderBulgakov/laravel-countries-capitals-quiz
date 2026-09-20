<?php

use App\Enums\QuizMode;
use App\Enums\QuizOutcome;
use App\Models\Capital;
use App\Models\Country;
use App\Services\Quiz\GuestQuizSession;

it('completes the attempt after answering all questions correctly', function () {
    Country::factory()
        ->count(20)
        ->has(Capital::factory())
        ->create(['region' => 'Europe']);

    $quizSession = app(GuestQuizSession::class);
    $quizSession->start(QuizMode::CountryToCapital);

    $totalQuestions = session('quiz.total_questions');

    for ($i = 1; $i <= $totalQuestions; $i++) {
        $pending = session('quiz.pending');
        $targetId = $pending['target_country_id'];

        $result = $quizSession->submitAnswer($targetId);
        expect($result->correct)->toBeTrue();

        if ($i < $totalQuestions) {
            expect($result->finished)->toBeFalse();
        }
    }

    expect($result->finished)->toBeTrue();
    expect($result->score)->toBe(10);
    expect($result->outcome)->toBe(QuizOutcome::Completed);
});
