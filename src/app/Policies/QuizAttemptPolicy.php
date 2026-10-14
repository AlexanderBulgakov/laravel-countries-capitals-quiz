<?php

namespace App\Policies;

use App\Models\QuizAttempt;
use App\Models\User;
use Illuminate\Auth\Access\Response;

class QuizAttemptPolicy
{
    /**
     * Determine whether the user can view the model.
     */
    public function view(User $user, QuizAttempt $quizAttempt): Response
    {
        return $quizAttempt->user()->is($user) ? Response::allow() : Response::denyAsNotFound();
    }
}
