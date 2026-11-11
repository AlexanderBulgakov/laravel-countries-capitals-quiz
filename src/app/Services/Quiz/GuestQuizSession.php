<?php

namespace App\Services\Quiz;

use App\Enums\Difficulty;
use App\Enums\Lifeline;
use App\Enums\QuizMode;
use App\Enums\QuizOutcome;
use App\Exceptions\LifelineUnavailable;

/**
 * Tracks an in-progress quiz attempt via the session. Despite the name,
 * this will keep being used once logged-in users exist too.
 */
class GuestQuizSession
{
    public const TOTAL_QUESTIONS = 10;

    // 15 "visible" seconds for the answer + 1 second hidden behind the loader
    // (the first question — the initial loader; subsequent questions — the result
    // display delay). Subtracting these 1 second from the displayed counter is
    // handled entirely on the JS side (resources/js/app.js); question_started_at
    // is not adjusted here.
    private const DURATION_SECONDS = 16;

    // Added to the current question's deadline; question_started_at never moves.
    public const EXTRA_TIME_SECONDS = 10;

    public function __construct(private QuestionGenerator $generator) {}

    public function start(QuizMode $mode): GeneratedQuestion
    {
        $question = $this->generator->generate($mode, [], Difficulty::forQuestion(1));

        session(['quiz' => [
            'mode' => $mode->value,
            'total_questions' => self::TOTAL_QUESTIONS,
            'question_number' => 1,
            'score' => 0,
            'used_country_ids' => [$question->targetCountryId],
            'pending' => $this->freshPending($question),
            'answers' => [],
            'used_lifelines' => [],
        ]]);

        return $question;
    }

    public function current(): ?GeneratedQuestion
    {
        $state = session('quiz');

        // No 'quiz' key at all → never started a quiz. 'quiz' present but no
        // 'pending' → the previous attempt already finished (pending gets
        // cleared on finish, see submitAnswer()) — either way, nothing to resume.
        if (! $state || ! isset($state['pending'])) {
            return null;
        }

        return new GeneratedQuestion(
            mode: QuizMode::from($state['mode']),
            targetCountryId: $state['pending']['target_country_id'],
            optionCountryIds: $state['pending']['option_country_ids'],
        );
    }

    public function submitAnswer(?int $selectedCountryId): AnswerResult
    {
        $state = session('quiz');
        $pending = $state['pending'];

        $expired = $this->isExpired($pending);
        $validOption = ! is_null($selectedCountryId)
            && in_array($selectedCountryId, $pending['option_country_ids'], true);
        $correct = ! $expired && $validOption && $selectedCountryId === $pending['target_country_id'];

        // Late or forged answers are stored as unanswered, never as the forged id.
        $chosenCountryId = ($expired || ! $validOption) ? null : $selectedCountryId;

        $state = $this->recordAnswer($state, $pending, $chosenCountryId);

        if (! $correct) {
            return $this->finishAttempt(
                state: $state,
                pending: $pending,
                outcome: $expired ? QuizOutcome::Timeout : QuizOutcome::Failed,
                correct: false
            );
        }

        return $this->advance($state, $pending, $state['score'] + 1, correct: true);
    }

    private function recordAnswer(array $state, array $pending, ?int $chosenCountryId): array
    {
        $state['answers'][] = [
            'question_number' => $state['question_number'],
            'target_country_id' => $pending['target_country_id'],
            'chosen_country_id' => $chosenCountryId,
            'option_country_ids' => $pending['option_country_ids'],
            'lifelines' => $pending['lifelines'] ?? [],
        ];

        return $state;
    }

    /**
     * Moves past the current question: finishes the attempt after the last one,
     * otherwise serves the next. Shared by answering and skipping.
     */
    private function advance(array $state, array $pending, int $score, bool $correct): AnswerResult
    {
        if ($state['question_number'] >= $state['total_questions']) {
            return $this->finishAttempt(
                state: [...$state, 'score' => $score],
                pending: $pending,
                outcome: QuizOutcome::Completed,
                correct: $correct,
            );
        }

        return $this->continueAttempt($state, $pending, $score, $correct);
    }

    private function finishAttempt(array $state, array $pending, QuizOutcome $outcome, bool $correct): AnswerResult
    {
        session(['quiz' => [
            ...$state,
            'pending' => null,
            'last_result' => [
                // Duplicated from $state['mode'] (already preserved by the spread
                // above) so lastResult() returns one self-contained snapshot for
                // the results page, instead of the controller having to read
                // session('quiz.mode') separately.
                'mode' => $state['mode'],
                'outcome' => $outcome->value,
                'score' => $state['score'],
                'stopped_at_question' => $state['question_number'],
                'money' => PrizeLadder::moneyFor($state['score']),
            ],
        ]]);

        return new AnswerResult(
            correct: $correct,
            correctCountryId: $pending['target_country_id'],
            score: $state['score'],
            finished: true,
            outcome: $outcome,
        );
    }

    private function continueAttempt(array $state, array $pending, int $score, bool $correct): AnswerResult
    {
        $mode = QuizMode::from($state['mode']);
        $nextQuestion = $this->generator->generate(
            $mode,
            $state['used_country_ids'],
            Difficulty::forQuestion($state['question_number'] + 1),
        );

        session(['quiz' => [
            ...$state,
            'question_number' => $state['question_number'] + 1,
            'score' => $score,
            'used_country_ids' => [...$state['used_country_ids'], $nextQuestion->targetCountryId],
            'pending' => $this->freshPending($nextQuestion),
        ]]);

        return new AnswerResult(
            correct: $correct,
            correctCountryId: $pending['target_country_id'],
            score: $score,
            finished: false,
            nextQuestion: $nextQuestion,
        );
    }

    public function remainingSeconds(): int
    {
        return max(0, $this->secondsLeft(session('quiz.pending')));
    }

    public function lastResult(): ?array
    {
        return session('quiz.last_result');
    }

    public function score(): int
    {
        return session('quiz.score', 0);
    }

    public function questionNumber(): int
    {
        return session('quiz.question_number', 1);
    }

    public function totalQuestions(): int
    {
        return self::TOTAL_QUESTIONS;
    }

    public function answers(): array
    {
        return session('quiz.answers', []);
    }

    public function hiddenOptionIds(): array
    {
        return session('quiz.pending.hidden_option_ids', []);
    }

    public function usedLifelines(): array
    {
        return session('quiz.used_lifelines', []);
    }

    /**
     * @return list<int> option ids the client should hide
     */
    public function useFiftyFifty(): array
    {
        $state = $this->claimLifeline(Lifeline::FiftyFifty);
        $pending = $state['pending'];

        $hiddenIds = collect($pending['option_country_ids'])
            ->reject(fn (int $id) => $id === $pending['target_country_id'])
            ->shuffle()
            ->take(2)
            ->values()
            ->all();

        $state['pending']['hidden_option_ids'] = $hiddenIds;
        session(['quiz' => $state]);

        return $hiddenIds;
    }

    /**
     * @return int seconds left on the extended timer
     */
    public function useExtraTime(): int
    {
        $state = $this->claimLifeline(Lifeline::ExtraTime);
        $state['pending']['extra_seconds'] = ($state['pending']['extra_seconds'] ?? 0) + self::EXTRA_TIME_SECONDS;
        session(['quiz' => $state]);

        return $this->secondsLeft($state['pending']);
    }

    public function skip(): AnswerResult
    {
        $state = $this->claimLifeline(Lifeline::Skip);
        $pending = $state['pending'];

        $state = $this->recordAnswer($state, $pending, null);

        // No point for a skipped question - which is why 10/10 becomes unreachable.
        return $this->advance($state, $pending, $state['score'], correct: false);
    }

    private function freshPending(GeneratedQuestion $question): array
    {
        return [
            'target_country_id' => $question->targetCountryId,
            'option_country_ids' => $question->optionCountryIds,
            'question_started_at' => now()->timestamp,
            'lifelines' => [],
            'hidden_option_ids' => [],
            'extra_seconds' => 0,
        ];
    }

    private function secondsLeft(array $pending): int
    {
        $elapsed = now()->timestamp - $pending['question_started_at'];

        return self::DURATION_SECONDS + ($pending['extra_seconds'] ?? 0) - $elapsed;
    }

    private function isExpired(array $pending): bool
    {
        return $this->secondsLeft($pending) <= 0;
    }

    /**
     * Validates and marks the lifeline as spent, returning the updated state
     * WITHOUT saving it — the caller adds its own effect and saves once.
     */
    private function claimLifeline(Lifeline $lifeline): array
    {
        $state = session('quiz');

        if (! $state || ! isset($state['pending'])) {
            throw LifelineUnavailable::noActiveQuiz();
        }

        if ($this->isExpired($state['pending'])) {
            throw LifelineUnavailable::questionExpired();
        }

        if (in_array($lifeline->value, $state['used_lifelines'] ?? [], true)) {
            throw LifelineUnavailable::alreadyUsed();
        }

        $state['used_lifelines'] = [...($state['used_lifelines'] ?? []), $lifeline->value];
        $state['pending']['lifelines'] = [...($state['pending']['lifelines'] ?? []), $lifeline->value];

        return $state;
    }
}
