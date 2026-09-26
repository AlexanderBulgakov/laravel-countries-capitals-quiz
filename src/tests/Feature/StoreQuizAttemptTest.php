<?php

use App\Enums\QuizMode;
use App\Enums\QuizOutcome;
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
});

it('stores a quiz attempt with the completed outcome', function () {
    actingAs($this->user);

    Country::factory()
        ->count(20)
        ->has(Capital::factory())
        ->create(['region' => 'Europe']);

    get(route('quiz.start', QuizMode::CountryToCapital));

    $totalQuestions = session('quiz.total_questions');

    for ($i = 1; $i <= $totalQuestions; $i++) {
        $targetId = session('quiz.pending.target_country_id');

        postJson(route('quiz.answer'), ['option_id' => $targetId]);
    }

    $attempt = QuizAttempt::sole();

    expect($attempt->outcome)->toBe(QuizOutcome::Completed);
    expect($attempt->score)->toBe(10);
    expect($attempt->stopped_at_question)->toBe(10);
    expect($attempt->user_id)->toBe($this->user->id);
});

it('stores a quiz attempt with the failed outcome', function () {
    actingAs($this->user);

    Country::factory()
        ->count(20)
        ->has(Capital::factory())
        ->create(['region' => 'Europe']);

    get(route('quiz.start', QuizMode::CountryToCapital));

    $targetId = session('quiz.pending.target_country_id');
    postJson(route('quiz.answer'), ['option_id' => $targetId]);
    postJson(route('quiz.answer'), ['option_id' => -1]);

    $attempt = QuizAttempt::sole();

    expect($attempt->outcome)->toBe(QuizOutcome::Failed);
    expect($attempt->score)->toBe(1);
    expect($attempt->stopped_at_question)->toBe(2);
    expect($attempt->user_id)->toBe($this->user->id);
});

it('stores a quiz attempt with the timeout outcome', function () {
    actingAs($this->user);

    Country::factory()
        ->count(20)
        ->has(Capital::factory())
        ->create(['region' => 'Europe']);

    get(route('quiz.start', QuizMode::CountryToCapital));

    session(['quiz.pending.question_started_at' => now()->subMinutes(5)->timestamp]);
    $targetId = session('quiz.pending.target_country_id');
    postJson(route('quiz.answer'), ['option_id' => $targetId]);

    $attempt = QuizAttempt::sole();

    expect($attempt->outcome)->toBe(QuizOutcome::Timeout);
    expect($attempt->score)->toBe(0);
    expect($attempt->stopped_at_question)->toBe(1);
    expect($attempt->user_id)->toBe($this->user->id);
});

it('does not store a quiz attempt for a guest', function () {
    Country::factory()
        ->count(20)
        ->has(Capital::factory())
        ->create(['region' => 'Europe']);

    get(route('quiz.start', QuizMode::CountryToCapital));

    $totalQuestions = session('quiz.total_questions');

    for ($i = 1; $i <= $totalQuestions; $i++) {
        $targetId = session('quiz.pending.target_country_id');
        postJson(route('quiz.answer'), ['option_id' => $targetId]);
    }

    assertDatabaseCount('quiz_attempts', 0);
});

it('does not store a quiz attempt with each answer', function () {
    actingAs($this->user);

    Country::factory()
        ->count(20)
        ->has(Capital::factory())
        ->create(['region' => 'Europe']);

    get(route('quiz.start', QuizMode::CountryToCapital));

    $totalQuestions = session('quiz.total_questions');

    for ($i = 1; $i <= $totalQuestions; $i++) {
        $targetId = session('quiz.pending.target_country_id');
        postJson(route('quiz.answer'), ['option_id' => $targetId]);

        if ($i < $totalQuestions) {
            assertDatabaseCount('quiz_attempts', 0);
        }
    }

    assertDatabaseCount('quiz_attempts', 1);
});

it('does not store a duplicate quiz attempt on a replayed answer request', function () {
    actingAs($this->user);

    Country::factory()
        ->count(20)
        ->has(Capital::factory())
        ->create(['region' => 'Europe']);

    get(route('quiz.start', QuizMode::CountryToCapital));

    $totalQuestions = session('quiz.total_questions');

    for ($i = 1; $i <= $totalQuestions; $i++) {
        $targetId = session('quiz.pending.target_country_id');
        postJson(route('quiz.answer'), ['option_id' => $targetId]);
    }

    postJson(route('quiz.answer'), ['option_id' => $targetId])->assertStatus(409);

    assertDatabaseCount('quiz_attempts', 1);
});
