<?php

use App\Enums\QuizMode;
use App\Models\Capital;
use App\Models\Country;
use App\Services\Quiz\GuestQuizSession;

it('advances to the next question when the answer is correct', function () {
    Country::factory()
        ->count(8)
        ->has(Capital::factory())
        ->create(['region' => 'Europe']);

    $quizSession = app(GuestQuizSession::class);
    $quizSession->start(QuizMode::CountryToCapital);

    $pending = session('quiz.pending');
    $targetId = $pending['target_country_id'];

    $result = $quizSession->submitAnswer($targetId);

    $usedCountryIds = session('quiz.used_country_ids');

    expect($result->correct)->toBeTrue();
    expect($result->finished)->toBeFalse();
    expect($result->score)->toBe(1);
    expect($result->nextQuestion->optionCountryIds)->toHaveCount(4);
    expect($usedCountryIds)->toContain($targetId);
});
