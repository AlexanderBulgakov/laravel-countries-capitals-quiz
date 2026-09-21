<?php

use App\Enums\QuizOutcome;

it('redirects to the landing page when there is no result to show', function () {
    $this->get(route('quiz.results'))
        ->assertRedirect(route('quiz.landing'));
});

it('shows the completed message', function () {
    session(['quiz' => ['last_result' => [
        'outcome' => QuizOutcome::Completed->value,
        'score' => 10,
        'stopped_at_question' => 10,
    ]]]);

    $this->get(route('quiz.results'))
        ->assertOk()
        ->assertSee('You completed the quiz!');
});
