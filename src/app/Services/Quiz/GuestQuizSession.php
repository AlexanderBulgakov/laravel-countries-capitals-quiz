<?php

namespace App\Services\Quiz;

use App\Enums\QuizMode;

/**
 * Tracks an in-progress quiz attempt via the session. Despite the name,
 * this will keep being used once logged-in users exist too.
 */
class GuestQuizSession
{
    private const TOTAL_QUESTIONS = 10;
    private const DURATION_SECONDS = 15;

    public function __construct(private QuestionGenerator $generator) {}

    public function start(QuizMode $mode): GeneratedQuestion
    {
        $question = $this->generator->generate($mode);

        session(['quiz' => [
            'mode' => $mode->value,
            'total_questions' => self::TOTAL_QUESTIONS,
            'question_number' => 1,
            'score' => 0,
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
        // TODO
        return new AnswerResult(
            correct: false,
            correctCountryId: 1,
            score: 1,
            finished: false,
            outcome: null,
            nextQuestion: null
        );
    }

    public function remainingSeconds(): int
    {
        // TODO
        return 1;
    }

    public function lastResult(): ?array
    {
        // TODO
        return null;
    }
}
