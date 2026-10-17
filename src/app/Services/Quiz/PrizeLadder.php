<?php

namespace App\Services\Quiz;

class PrizeLadder
{
    // Descending on purpose: the first threshold the score reaches is the highest one.
    public const THRESHOLDS = [
        10 => 10_000,
        6 => 1_000,
        3 => 100,
    ];

    public static function moneyFor(int $score): int
    {
        foreach (self::THRESHOLDS as $minScore => $money) {
            if ($score >= $minScore) {
                return $money;
            }
        }

        return 0;
    }
}
