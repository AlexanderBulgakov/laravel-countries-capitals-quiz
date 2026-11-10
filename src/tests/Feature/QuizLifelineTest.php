<?php

use App\Enums\Lifeline;
use App\Enums\QuizMode;
use App\Enums\QuizOutcome;
use App\Models\Capital;
use App\Models\Country;
use App\Models\QuizAttempt;
use App\Models\User;
use App\Services\Quiz\GuestQuizSession;
use App\Services\Quiz\PrizeLadder;

use function Pest\Laravel\actingAs;
use function Pest\Laravel\get;
use function Pest\Laravel\postJson;

beforeEach(function () {
    $this->freezeTime();

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

it('adds extra time to the remaining seconds', function () {
    get(route('quiz.start', QuizMode::CountryToCapital));

    $this->travel(5)->seconds();

    // GuestQuizSession::DURATION_SECONDS
    postJson(route('quiz.lifeline', Lifeline::ExtraTime))
        ->assertOk()
        ->assertJson(['remaining_seconds' => 16 - 5 + GuestQuizSession::EXTRA_TIME_SECONDS]);
});

it('accepts an answer after the base timer when extra time was used', function () {
    get(route('quiz.start', QuizMode::CountryToCapital));

    postJson(route('quiz.lifeline', Lifeline::ExtraTime))->assertOk();

    // past the base deadline, inside the extended one
    $this->travel(20)->seconds();

    postJson(route('quiz.answer'), ['option_id' => session('quiz.pending.target_country_id')])
        ->assertJson(['correct' => true, 'finished' => false]);
});

it('still times out after the extended deadline', function () {
    get(route('quiz.start', QuizMode::CountryToCapital));

    postJson(route('quiz.lifeline', Lifeline::ExtraTime))->assertOk();

    $this->travel(16 + GuestQuizSession::EXTRA_TIME_SECONDS)->seconds(); // GuestQuizSession::DURATION_SECONDS

    postJson(route('quiz.answer'), ['option_id' => session('quiz.pending.target_country_id')])
        ->assertOk()
        ->assertJson(['outcome' => 'timeout']);
});

it('treats extra time requested after expiry as a timeout, not a bonus', function () {
    get(route('quiz.start', QuizMode::CountryToCapital));

    $this->travel(16)->seconds(); // GuestQuizSession::DURATION_SECONDS

    postJson(route('quiz.lifeline', Lifeline::ExtraTime))
        ->assertConflict()
        ->assertJson(['error' => 'question_expired']);

    expect(session('quiz.pending.extra_seconds'))->toBe(0);

    // Even the right option can't be saved by the rejected bonus.
    postJson(route('quiz.answer'), ['option_id' => session('quiz.pending.target_country_id')])
        ->assertJson(['outcome' => 'timeout']);
});

it('resets extra time on the next question', function () {
    get(route('quiz.start', QuizMode::CountryToCapital));

    postJson(route('quiz.lifeline', Lifeline::ExtraTime))
        ->assertOk()
        ->assertJson(['remaining_seconds' => 16 + GuestQuizSession::EXTRA_TIME_SECONDS]);

    postJson(route('quiz.answer'), ['option_id' => session('quiz.pending.target_country_id')])
        ->assertOk()
        ->assertJson(['remaining_seconds' => 16]);
});

it('moves to the next question without scoring a skipped one', function () {
    get(route('quiz.start', QuizMode::CountryToCapital));

    postJson(route('quiz.answer'), ['option_id' => session('quiz.pending.target_country_id')])
        ->assertOk()
        ->assertJson(['correct' => true]);

    postJson(route('quiz.lifeline', Lifeline::Skip))
        ->assertOk()
        ->assertJson([
            'correct' => false,
            'score' => 1,           // unchanged - the skip earned nothing
            'finished' => false,
            'question_number' => 3, // moved on
        ]);

    // The fresh question after a skip plays as usual.
    postJson(route('quiz.answer'), ['option_id' => session('quiz.pending.target_country_id')])
        ->assertJson(['correct' => true, 'score' => 2, 'question_number' => 4]);
});

it('records a skipped question as unanswered with the skip lifeline', function () {
    actingAs(User::factory()->create());
    get(route('quiz.start', QuizMode::CountryToCapital));

    postJson(route('quiz.lifeline', Lifeline::Skip))
        ->assertOk();

    $wrongOptionId = collect(session('quiz.pending.option_country_ids'))
        ->reject(fn (int $id) => $id === session('quiz.pending.target_country_id'))
        ->first();
    postJson(route('quiz.answer'), ['option_id' => $wrongOptionId]);

    $answers = QuizAttempt::sole()->answers;

    expect($answers->pluck('lifelines')->all())
        ->toBe([[Lifeline::Skip->value], []]);

    $skipped = $answers->first();

    expect($skipped->chosen_country_id)->toBeNull()
        ->and($skipped->lifelines)->toBe([Lifeline::Skip->value]);
});

it('completes the attempt with a score of 9 when the last question is skipped', function () {
    get(route('quiz.start', QuizMode::CountryToCapital));

    foreach (range(1, 9) as $_) {
        postJson(route('quiz.answer'), ['option_id' => session('quiz.pending.target_country_id')])
            ->assertOk();
    }

    postJson(route('quiz.lifeline', Lifeline::Skip))
        ->assertOk()
        ->assertJson([
            'finished' => true,
            'outcome' => QuizOutcome::Completed->value,
            'score' => 9,
            'question_number' => 10,
        ]);
});

it('stores a completed attempt that used a skip', function () {
    actingAs(User::factory()->create());
    get(route('quiz.start', QuizMode::CountryToCapital));

    foreach (range(1, 9) as $_) {
        postJson(route('quiz.answer'), ['option_id' => session('quiz.pending.target_country_id')])
            ->assertOk();
    }

    postJson(route('quiz.lifeline', Lifeline::Skip))
        ->assertOk();

    $quizAttempt = QuizAttempt::sole();

    expect($quizAttempt->outcome)->toBe(QuizOutcome::Completed)
        ->and($quizAttempt->score)->toBe(9)
        ->and($quizAttempt->stopped_at_question)->toBe(10)
        ->and($quizAttempt->money)->toBe(PrizeLadder::moneyFor(9));
});

it('rejects a second skip in the same attempt', function () {
    get(route('quiz.start', QuizMode::CountryToCapital));

    postJson(route('quiz.lifeline', Lifeline::Skip))->assertOk();
    $targetAfterSkip = session('quiz.pending.target_country_id');

    postJson(route('quiz.lifeline', Lifeline::Skip))
        ->assertConflict()
        ->assertJson(['error' => 'lifeline_already_used']);

    // The rejected skip must not move the game on.
    expect(session('quiz.question_number'))->toBe(2)
        ->and(session('quiz.pending.target_country_id'))->toBe($targetAfterSkip);
});

it('shows a skipped question as skipped even when the attempt timed out', function () {
    actingAs(User::factory()->create());
    get(route('quiz.start', QuizMode::CountryToCapital));

    postJson(route('quiz.lifeline', Lifeline::Skip))->assertOk();

    $this->travel(16)->seconds(); // GuestQuizSession::DURATION_SECONDS

    postJson(route('quiz.answer'), ['option_id' => session('quiz.pending.target_country_id')])
        ->assertOk();

    get(route('quiz.attempts.show', QuizAttempt::sole()))
        ->assertOk()
        ->assertSeeInOrder(['Skipped.', 'No answer — time ran out.']);
});
