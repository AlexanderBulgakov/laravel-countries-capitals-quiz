<?php

use App\Enums\QuizMode;
use App\Models\Capital;
use App\Models\Country;
use App\Models\QuizAttempt;
use App\Models\User;

use function Pest\Laravel\actingAs;
use function Pest\Laravel\assertDatabaseCount;
use function Pest\Laravel\get;
use function Pest\Laravel\postJson;

beforeEach(function () {
    $this->user = User::factory()->create();

    Country::factory()
        ->count(20)
        ->has(Capital::factory())
        ->create(['region' => 'Europe']);
});

it('stores every answer of a completed attempt in question order', function () {
    actingAs($this->user);

    get(route('quiz.start', QuizMode::CountryToCapital));

    $totalQuestions = session('quiz.total_questions');

    for ($i = 1; $i <= $totalQuestions; $i++) {
        $targetId = session('quiz.pending.target_country_id');

        postJson(route('quiz.answer'), ['option_id' => $targetId]);
    }

    $answers = QuizAttempt::sole()->answers;

    expect($answers)->toHaveCount(10);
    expect($answers->pluck('question_number')->all())->toBe(range(1, 10));
    expect($answers->every->isCorrect())->toBeTrue();
});

it('stores no chosen option when the answer arrives after the timer expired', function () {
    actingAs($this->user);

    get(route('quiz.start', QuizMode::CountryToCapital));

    $targetId = session('quiz.pending.target_country_id');
    session(['quiz.pending.question_started_at' => now()->subMinutes(5)->timestamp]);

    postJson(route('quiz.answer'), ['option_id' => $targetId]);

    $answer = QuizAttempt::sole()->answers->sole();

    expect($answer->target_country_id)->toBe($targetId);
    expect($answer->chosen_country_id)->toBeNull();
    expect($answer->isCorrect())->toBeFalse();
});

it('stores the chosen wrong option when the attempt fails', function () {
    actingAs($this->user);

    get(route('quiz.start', QuizMode::CountryToCapital));

    $targetId = session('quiz.pending.target_country_id');

    $wrongId = collect(session('quiz.pending.option_country_ids'))
        ->first(fn ($id) => $id !== $targetId);

    postJson(route('quiz.answer'), ['option_id' => $wrongId]);

    $answer = QuizAttempt::sole()->answers->sole();

    expect($answer->chosen_country_id)->toBe($wrongId);
});

it('stores no chosen option for a forged option id without breaking the save', function () {
    actingAs($this->user);

    get(route('quiz.start', QuizMode::CountryToCapital));

    postJson(route('quiz.answer'), ['option_id' => 0])->assertOk();

    $answer = QuizAttempt::sole()->answers->sole();

    expect($answer->chosen_country_id)->toBeNull();
});

it('does not store answers for a guest', function () {
    get(route('quiz.start', QuizMode::CountryToCapital));

    $targetId = session('quiz.pending.target_country_id');
    postJson(route('quiz.answer'), ['option_id' => $targetId]);
    postJson(route('quiz.answer'), ['option_id' => 0]);

    assertDatabaseCount('quiz_answers', 0);
});
