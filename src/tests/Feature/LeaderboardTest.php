<?php

use App\Enums\QuizMode;
use App\Models\Capital;
use App\Models\Country;
use App\Models\User;

use function Pest\Laravel\actingAs;
use function Pest\Laravel\get;
use function Pest\Laravel\postJson;

it('is publicly accessible to guests', function () {
    $this->get(route('leaderboard.index'))->assertOk();
});

it('ranks tied users at the same position without gaps', function () {
    $user1 = User::factory()->create();
    $user2 = User::factory()->create();
    $user3 = User::factory()->create();
    $user4 = User::factory()->create();
    $user5 = User::factory()->create();

    Country::factory()
        ->count(20)
        ->has(Capital::factory())
        ->create(['region' => 'Europe']);

    $users = [
        $user1,
        $user2,
        $user3,
        $user4,
        $user1,
        $user2,
    ];

    foreach ($users as $user) {
        actingAs($user);

        get(route('quiz.start', QuizMode::CountryToCapital));

        $totalQuestions = session('quiz.total_questions');

        for ($i = 1; $i <= $totalQuestions; $i++) {
            $targetId = session('quiz.pending.target_country_id');

            if ($user4->id === $user->id && $i > 1) {
                $targetId = 0;
            }

            postJson(route('quiz.answer'), ['option_id' => $targetId]);
        }
    }

    $response = get(route('leaderboard.index'))->assertOk();

    $rankings = $response->viewData('rankings');

    foreach ($rankings as $i => $data) {
        if ($i < 2) {
            // two users on the first place
            expect($data->rank)->toBe(1);
            // two users have completed=2
            expect($data->completed_count)->toBe(2);
        } elseif ($i === 2) {
            // one user on the second place
            expect($data->rank)->toBe(2);
        }
    }

    // other users failed($user4) and new registered($user5) not in top
    expect($rankings)->toHaveCount(3);
});
