<?php

namespace App\Services\Quiz;

use App\Enums\QuizOutcome;

/**
 * Result of submitting one answer (or a timeout, see GuestQuizSession).
 *
 * $outcome and $nextQuestion are mutually exclusive, driven by $finished:
 * still going → $nextQuestion is set, $outcome is null; the attempt just
 * ended (win or lose) → $outcome is set, no next question. $correctCountryId
 * is always the target's id regardless of $correct, so the caller can
 * reveal the right answer either way.
 */
final readonly class AnswerResult
{
    public function __construct(
        public bool $correct,
        public int $correctCountryId,
        public int $score,
        public bool $finished,
        public ?QuizOutcome $outcome = null,
        public ?GeneratedQuestion $nextQuestion = null,
    ) {}
}
