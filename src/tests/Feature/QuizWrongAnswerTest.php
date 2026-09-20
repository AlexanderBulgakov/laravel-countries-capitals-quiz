<?php

use App\Enums\QuizMode;
use App\Enums\QuizOutcome;
use App\Models\Capital;
use App\Models\Country;
use App\Services\Quiz\GuestQuizSession;

it('ends the attempt when the answer is wrong', function () {
    Country::factory()
        ->count(4)
        ->has(Capital::factory())
        ->create(['region' => 'Europe']);

    $quizSession = app(GuestQuizSession::class);
    $quizSession->start(QuizMode::CountryToCapital);

    $pending = session('quiz.pending');
    $wrongId = collect($pending['option_country_ids'])
        ->first(fn ($id) => $id !== $pending['target_country_id']);

    $result = $quizSession->submitAnswer($wrongId);

    expect($result->correct)->toBeFalse();
    expect($result->finished)->toBeTrue();
    expect($result->outcome)->toBe(QuizOutcome::Failed);
    expect($result->correctCountryId)->toBe($pending['target_country_id']);
});
