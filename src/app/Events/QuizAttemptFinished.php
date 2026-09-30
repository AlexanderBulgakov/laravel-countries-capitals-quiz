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

    /**
     * @param  list<array{question_number: int, target_country_id: int, chosen_country_id: ?int, option_country_ids: list<int>}>  $answers
     */
    public function __construct(
        public User $user,
        public QuizMode $mode,
        public QuizOutcome $outcome,
        public int $score,
        public int $stoppedAtQuestion,
        public array $answers
    ) {}
}
