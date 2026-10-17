<?php

use App\Services\Quiz\GuestQuizSession;
use App\Services\Quiz\PrizeLadder;

it('awards the highest threshold the score has reached', function (int $score, int $money) {
    expect(PrizeLadder::moneyFor($score))->toBe($money);
})->with([
    'no correct answers' => [0, 0],
    'below the first threshold' => [2, 0],
    'exactly the first threshold' => [3, 100],
    'just below the second' => [5, 100],
    'exactly the second threshold' => [6, 1_000],
    'one short of the top' => [9, 1_000],
    'all questions right' => [10, 10_000],
]);

it('puts the top threshold at the total number of questions', function () {
    expect(max(array_keys(PrizeLadder::THRESHOLDS)))->toBe(GuestQuizSession::TOTAL_QUESTIONS);
});
