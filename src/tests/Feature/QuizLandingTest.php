<?php

use App\Enums\QuizMode;

it('shows links to both quiz modes', function () {
    $this->get(route('quiz.landing'))
        ->assertOk()
        ->assertSee(route('quiz.start', QuizMode::CountryToCapital))
        ->assertSee(route('quiz.start', QuizMode::CapitalToCountry));
});
