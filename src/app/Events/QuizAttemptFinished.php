<?php

namespace App\Events;

use App\Enums\QuizMode;
use App\Enums\QuizOutcome;
use App\Models\User;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class QuizAttemptFinished
{
    use Dispatchable, SerializesModels;

    public function __construct(
        public User $user,
        public QuizMode $mode,
        public QuizOutcome $outcome,
        public int $score,
        public int $stoppedAtQuestion
    ) {}
}
