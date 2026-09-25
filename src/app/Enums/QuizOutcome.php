<?php

namespace App\Enums;

/**
 * Failed and Timeout end the attempt identically (sudden-death, no
 * further questions) — kept as separate cases only so the results page
 * can show different copy ("wrong answer" vs "ran out of time"), not
 * because the game logic treats them differently.
 */
enum QuizOutcome: string
{
    case Completed = 'completed';
    case Failed = 'failed';
    case Timeout = 'timeout';

    public function label(): string
    {
        return match ($this) {
            self::Completed => 'You completed the quiz!',
            self::Failed => 'Wrong answer — game over.',
            self::Timeout => "Time's up — game over.",
        };
    }

    public function shortLabel(): string
    {
        return match ($this) {
            self::Completed => 'Completed',
            self::Failed => 'Wrong answer',
            self::Timeout => 'Timed out',
        };
    }
}
