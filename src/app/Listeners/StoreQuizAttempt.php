<?php

namespace App\Listeners;

use App\Events\QuizAttemptFinished;
use App\Models\QuizAttempt;
use Illuminate\Support\Facades\DB;

class StoreQuizAttempt
{
    /**
     * Handle the event.
     */
    public function handle(QuizAttemptFinished $event): void
    {
        DB::transaction(function () use ($event) {
            $attempt = QuizAttempt::create([
                'user_id' => $event->user->id,
                'mode' => $event->mode,
                'outcome' => $event->outcome,
                'score' => $event->score,
                'stopped_at_question' => $event->stoppedAtQuestion,
            ]);

            $attempt->answers()->createMany($event->answers);
        });
    }
}
