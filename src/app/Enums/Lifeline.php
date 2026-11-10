<?php

namespace App\Enums;

enum Lifeline: string
{
    case FiftyFifty = 'fifty-fifty';
    case ExtraTime = 'extra-time';
    case Skip = 'skip';

    public function label(): string
    {
        return match ($this) {
            self::FiftyFifty => '50/50',
            self::ExtraTime => '+10s',
            self::Skip => 'Skip',
        };
    }
}
