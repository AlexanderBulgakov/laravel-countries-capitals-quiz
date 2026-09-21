<?php

namespace App\Services\Quiz;

use App\Enums\QuizMode;
use App\Enums\QuizOutcome;

/**
 * Tracks an in-progress quiz attempt via the session. Despite the name,
 * this will keep being used once logged-in users exist too.
 */
class GuestQuizSession
{
    private const TOTAL_QUESTIONS = 10;

    // 15 "visible" seconds for the answer + 1 second hidden behind the loader
    // (the first question — the initial loader; subsequent questions — the result
    // display delay). Subtracting these 1 second from the displayed counter is
    // handled entirely on the JS side (resources/js/app.js); question_started_at
    // is not adjusted here.
    private const DURATION_SECONDS = 16;

    public function __construct(private QuestionGenerator $generator) {}

    public function start(QuizMode $mode): GeneratedQuestion
    {
        $question = $this->generator->generate($mode);

        session(['quiz' => [
            'mode' => $mode->value,
            'total_questions' => self::TOTAL_QUESTIONS,
            'question_number' => 1,
            'score' => 0,
            'used_country_ids' => [$question->targetCountryId],
            'pending' => [
                'target_country_id' => $question->targetCountryId,
                'option_country_ids' => $question->optionCountryIds,
                'question_started_at' => now()->timestamp,
            ],
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

        $expired = now()->timestamp - $pending['question_started_at'] >= self::DURATION_SECONDS;
        $validOption = ! is_null($selectedCountryId)
            && in_array($selectedCountryId, $pending['option_country_ids'], true);
        $correct = ! $expired && $validOption && $selectedCountryId === $pending['target_country_id'];

        if (! $correct) {
            return $this->finishAttempt(
                state: $state,
                pending: $pending,
                outcome: $expired ? QuizOutcome::Timeout : QuizOutcome::Failed,
                correct: false
            );
        }

        $score = $state['score'] + 1;

        if ($state['total_questions'] < $state['question_number'] + 1) {
            return $this->finishAttempt(
                state: [...$state, 'score' => $score],
                pending: $pending,
                outcome: QuizOutcome::Completed,
                correct: true
            );
        }

        return $this->continueAttempt(
            state: $state,
            pending: $pending,
            score: $score
        );
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

    private function continueAttempt(array $state, array $pending, int $score): AnswerResult
    {
        $mode = QuizMode::from($state['mode']);
        $nextQuestion = $this->generator->generate($mode, $state['used_country_ids']);

        session(['quiz' => [
            ...$state,
            'question_number' => $state['question_number'] + 1,
            'score' => $score,
            'used_country_ids' => [...$state['used_country_ids'], $nextQuestion->targetCountryId],
            'pending' => [
                'target_country_id' => $nextQuestion->targetCountryId,
                'option_country_ids' => $nextQuestion->optionCountryIds,
                'question_started_at' => now()->timestamp,
            ],
        ]]);

        return new AnswerResult(
            correct: true,
            correctCountryId: $pending['target_country_id'],
            score: $score,
            finished: false,
            nextQuestion: $nextQuestion,
        );
    }

    public function remainingSeconds(): int
    {
        $pending = session('quiz.pending');

        $elapsed = now()->timestamp - $pending['question_started_at'];

        return max(0, self::DURATION_SECONDS - $elapsed);
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
}
