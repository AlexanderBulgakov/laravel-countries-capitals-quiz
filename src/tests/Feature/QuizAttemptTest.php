<?php

use App\Enums\QuizMode;
use App\Models\Capital;
use App\Models\Country;
use App\Models\QuizAttempt;
use App\Models\User;

use function Pest\Laravel\actingAs;
use function Pest\Laravel\get;
use function Pest\Laravel\postJson;

beforeEach(function () {
    $this->user = User::factory()->create();
    $this->otherUser = User::factory()->create();
});

it('shows the owner a per-question breakdown of their attempt', function () {
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

    get(route('quiz.attempts.show', $attempt))
        ->assertOk()
        ->assertSee('Question 1:')
        ->assertSee('Question 10:')
        ->assertSee('Prize: 10,000')
        ->assertDontSee('Details are not available');
});

it('returns 404 for another user\'s attempt', function () {
    $attempt = QuizAttempt::factory()->for($this->otherUser)->create();

    actingAs($this->user)->get(route('quiz.attempts.show', $attempt))->assertNotFound();
});

it('redirects a guest to the login page', function () {
    $attempt = QuizAttempt::factory()->for($this->user)->create();

    get(route('quiz.attempts.show', $attempt))->assertRedirect(route('login'));
});

it('returns 404 for a non-existent attempt', function () {
    actingAs($this->user);

    get(route('quiz.attempts.show', QuizAttempt::max('id') + 1))->assertNotFound();
});

it('shows a placeholder for an attempt without saved answers', function () {
    $attempt = QuizAttempt::factory()->for($this->user)->create();

    actingAs($this->user)
        ->get(route('quiz.attempts.show', $attempt))
        ->assertOk()
        ->assertSee('Details are not available for this attempt.');
});
