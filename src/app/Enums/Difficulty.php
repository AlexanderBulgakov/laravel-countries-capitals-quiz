<?php

namespace App\Enums;

enum Difficulty: int
{
    case Easy = 1;
    case Medium = 2;
    case Hard = 3;

    public static function forQuestion(int $number): self
    {
        // Steps up right after each PrizeLadder safe amount (3, 6).
        return match (true) {
            $number <= 3 => self::Easy,
            $number <= 6 => self::Medium,
            default => self::Hard,
        };
    }
}
