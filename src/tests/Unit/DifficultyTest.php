<?php

use App\Enums\Difficulty;

it('maps each question number to its difficulty', function (int $number, Difficulty $difficulty) {
    expect(Difficulty::forQuestion($number))->toBe($difficulty);
})->with([
    'first question' => [1, Difficulty::Easy],
    'last easy' => [3, Difficulty::Easy],
    'first medium' => [4, Difficulty::Medium],
    'last medium' => [6, Difficulty::Medium],
    'first hard' => [7, Difficulty::Hard],
    'last question' => [10, Difficulty::Hard],
]);
