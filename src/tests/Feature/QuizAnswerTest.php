<?php

use App\Enums\QuizMode;
use App\Models\Capital;
use App\Models\Country;

it('returns json with the next question when the answer is correct', function () {
    Country::factory()
        ->count(8)
        ->has(Capital::factory())
        ->create(['region' => 'Europe']);

    $this->get(route('quiz.start', QuizMode::CountryToCapital))->assertOk();

    $targetId = session('quiz.pending.target_country_id');

    $this->postJson(route('quiz.answer'), ['option_id' => $targetId])
        ->assertOk()
        ->assertJson([
            'correct' => true,
            'finished' => false,
            'score' => 1,
        ])
        ->assertJsonStructure(['next_question' => ['prompt', 'options']]);
});

it('returns a 409 when there is no active quiz to answer', function () {
    $this->postJson(route('quiz.answer'), ['option_id' => 1])
        ->assertStatus(409);
});
