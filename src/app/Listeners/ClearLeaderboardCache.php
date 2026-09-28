<?php

namespace App\Listeners;

use App\Enums\QuizOutcome;
use App\Events\QuizAttemptFinished;
use Illuminate\Support\Facades\Cache;

class ClearLeaderboardCache
{
    /**
     * Handle the event.
     */
    public function handle(QuizAttemptFinished $event): void
    {
        if ($event->outcome !== QuizOutcome::Completed) {
            return;
        }

        Cache::tags(['leaderboard'])->flush();
    }
}
