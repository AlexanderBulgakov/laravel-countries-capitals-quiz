<?php

use App\Enums\QuizMode;
use App\Enums\QuizOutcome;

it('redirects to the landing page when there is no result to show', function () {
    $this->get(route('quiz.results'))
        ->assertRedirect(route('quiz.landing'));
});

it('shows the completed message and the prize', function () {
    session(['quiz' => ['last_result' => [
        'mode' => QuizMode::CountryToCapital->value,
        'outcome' => QuizOutcome::Completed->value,
        'score' => 10,
        'stopped_at_question' => 10,
        'money' => 10_000,
    ]]]);

    $this->get(route('quiz.results'))
        ->assertOk()
        ->assertSee('Prize: 10,000')
        ->assertSee('You completed the quiz!');
});

it('shows a zero prize for a result saved before prizes existed', function () {
    session(['quiz' => ['last_result' => [
        'mode' => QuizMode::CountryToCapital->value,
        'outcome' => QuizOutcome::Completed->value,
        'score' => 10,
        'stopped_at_question' => 10,
    ]]]);

    $this->get(route('quiz.results'))
        ->assertOk()
        ->assertSee('Prize: 0');
});
