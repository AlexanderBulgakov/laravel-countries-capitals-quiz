<?php

namespace App\Listeners;

use App\Events\QuizAttemptFinished;
use App\Models\QuizAttempt;

class StoreQuizAttempt
{
    /**
     * Handle the event.
     */
    public function handle(QuizAttemptFinished $event): void
    {
        QuizAttempt::create([
            'user_id' => $event->user->id,
            'mode' => $event->mode,
            'outcome' => $event->outcome,
            'score' => $event->score,
            'stopped_at_question' => $event->stoppedAtQuestion,
        ]);
    }
}
