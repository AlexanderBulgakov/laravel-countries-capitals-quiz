<?php

use App\Enums\Lifeline;
use App\Enums\QuizMode;
use App\Models\Capital;
use App\Models\Country;
use App\Models\QuizAttempt;
use App\Models\User;

use function Pest\Laravel\actingAs;
use function Pest\Laravel\get;
use function Pest\Laravel\postJson;

beforeEach(function () {
    Country::factory()
        ->count(20)
        ->has(Capital::factory())
        ->create(['region' => 'Europe']);
});

it('hides two wrong options and never the correct one', function () {
    foreach (range(1, 10) as $_) {
        session()->forget('quiz');
        get(route('quiz.start', QuizMode::CountryToCapital));

        $hiddenOptionIds = postJson(route('quiz.lifeline', Lifeline::FiftyFifty))
            ->assertOk()
            ->json('hidden_option_ids');

        expect($hiddenOptionIds)->toHaveCount(2)
            ->not->toContain(session('quiz.pending.target_country_id'))
            ->and(session('quiz.pending.option_country_ids'))->toContain(...$hiddenOptionIds);
    }
});

it('keeps hidden options and the spent lifeline after a page reload', function () {
    get(route('quiz.start', QuizMode::CountryToCapital));

    $hiddenOptionIds = postJson(route('quiz.lifeline', Lifeline::FiftyFifty))
        ->assertOk()
        ->json('hidden_option_ids');

    get(route('quiz.start', QuizMode::CountryToCapital))
        ->assertViewHas('hiddenOptionIds', $hiddenOptionIds)
        ->assertViewHas('usedLifelines', [Lifeline::FiftyFifty->value]);
});

it('rejects fifty-fifty when it was already used in this attempt', function () {
    get(route('quiz.start', QuizMode::CountryToCapital));

    $hiddenOptionIds = postJson(route('quiz.lifeline', Lifeline::FiftyFifty))
        ->assertOk()
        ->json('hidden_option_ids');

    postJson(route('quiz.lifeline', Lifeline::FiftyFifty))
        ->assertConflict()
        ->assertJson(['error' => 'lifeline_already_used']);

    expect(session('quiz.pending.hidden_option_ids'))->toBe($hiddenOptionIds);
});

it('keeps fifty-fifty spent on the next question', function () {
    get(route('quiz.start', QuizMode::CountryToCapital));

    postJson(route('quiz.lifeline', Lifeline::FiftyFifty))->assertOk();

    postJson(route('quiz.answer'), ['option_id' => session('quiz.pending.target_country_id')])
        ->assertJson(['correct' => true, 'finished' => false]);

    // The effect resets with the new question, the spent lifeline does not.
    expect(session('quiz.pending.hidden_option_ids'))->toBe([])
        ->and(session('quiz.pending.lifelines'))->toBe([])
        ->and(session('quiz.used_lifelines'))->toBe([Lifeline::FiftyFifty->value]);

    postJson(route('quiz.lifeline', Lifeline::FiftyFifty))
        ->assertConflict()
        ->assertJson(['error' => 'lifeline_already_used']);
});

it('rejects a lifeline after the question timer expired', function () {
    get(route('quiz.start', QuizMode::CountryToCapital));

    $this->travel(16)->seconds(); // GuestQuizSession::DURATION_SECONDS

    postJson(route('quiz.lifeline', Lifeline::FiftyFifty))
        ->assertConflict()
        ->assertJson(['error' => 'question_expired']);

    expect(session('quiz.used_lifelines'))->toBe([])
        ->and(session('quiz.pending.lifelines'))->toBe([]);
});

it('rejects a lifeline without an active quiz', function () {
    postJson(route('quiz.lifeline', Lifeline::FiftyFifty))
        ->assertConflict()
        ->assertJson(['error' => 'no_active_quiz']);
});

it('records lifelines on the answer they were used for', function () {
    actingAs(User::factory()->create());
    get(route('quiz.start', QuizMode::CountryToCapital));

    postJson(route('quiz.lifeline', Lifeline::FiftyFifty))->assertOk();
    postJson(route('quiz.answer'), ['option_id' => session('quiz.pending.target_country_id')]);

    $wrongOptionId = collect(session('quiz.pending.option_country_ids'))
        ->reject(fn (int $id) => $id === session('quiz.pending.target_country_id'))
        ->first();
    postJson(route('quiz.answer'), ['option_id' => $wrongOptionId]);

    expect(QuizAttempt::sole()->answers->pluck('lifelines')->all())
        ->toBe([[Lifeline::FiftyFifty->value], []]);
});

it('returns 404 for an unknown lifeline', function () {
    postJson(route('quiz.lifeline', 'unknown'))->assertNotFound();
});
