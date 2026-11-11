<?php

namespace App\Exceptions;

use Illuminate\Contracts\Debug\ShouldntReport;
use Illuminate\Http\JsonResponse;
use RuntimeException;

class LifelineUnavailable extends RuntimeException implements ShouldntReport
{
    private function __construct(public readonly string $reason)
    {
        parent::__construct("Lifeline unavailable: {$reason}");
    }

    public static function noActiveQuiz(): self
    {
        return new self('no_active_quiz');
    }

    public static function questionExpired(): self
    {
        return new self('question_expired');
    }

    public static function alreadyUsed(): self
    {
        return new self('lifeline_already_used');
    }

    public function render(): JsonResponse
    {
        return response()->json(['error' => $this->reason], 409);
    }
}
