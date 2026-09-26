<?php

use App\Models\QuizAttempt;
use App\Models\User;

use function Pest\Laravel\actingAs;
use function Pest\Laravel\assertGuest;
use function Pest\Laravel\get;

it('redirects unauthenticated users to the login page', function () {
    assertGuest();

    get('/quiz/attempts')
        ->assertRedirect('/login');
});

it('access authorized user to my attempts page', function () {
    $user = User::factory()->create();

    actingAs($user)
        ->get('/quiz/attempts')
        ->assertOk();
});

it('only shows the authenticated user\'s own attempts', function () {
    $user = User::factory()->create();
    $otherUser = User::factory()->create();

    QuizAttempt::factory()->completed()->for($user)->create();
    QuizAttempt::factory()->failed()->for($otherUser)->create();

    actingAs($user);

    $response = get('/quiz/attempts')->assertOk();

    $attempts = $response->viewData('attempts');

    expect($attempts)->toHaveCount(1);
    expect($attempts->first()->user_id)->toBe($user->id);
});

it('filters attempts by mode', function () {
    $user = User::factory()->create();

    QuizAttempt::factory()->countryToCapital()->for($user)->create();
    QuizAttempt::factory()->countryToCapital()->for($user)->create();
    QuizAttempt::factory()->capitalToCountry()->for($user)->create();

    actingAs($user);

    $response = get('/quiz/attempts?mode=country-capitals')->assertOk();

    $attempts = $response->viewData('attempts');

    expect($attempts)->toHaveCount(2);
});

it('filters attempts by outcome', function () {
    $user = User::factory()->create();

    QuizAttempt::factory()->completed()->for($user)->create();
    QuizAttempt::factory()->completed()->for($user)->create();
    QuizAttempt::factory()->failed()->for($user)->create();

    actingAs($user);

    $response = get('/quiz/attempts?outcome=completed')->assertOk();

    $attempts = $response->viewData('attempts');

    expect($attempts)->toHaveCount(2);
});

it('shows no attempts when the outcome filter matches nothing', function () {
    $user = User::factory()->create();

    QuizAttempt::factory()->completed()->for($user)->create();
    QuizAttempt::factory()->completed()->for($user)->create();
    QuizAttempt::factory()->failed()->for($user)->create();

    actingAs($user);

    $response = get('/quiz/attempts?outcome=timeout')->assertOk();

    $attempts = $response->viewData('attempts');

    expect($attempts)->toHaveCount(0);
});

it('ignores an invalid filter value and shows all attempts', function () {
    $user = User::factory()->create();

    QuizAttempt::factory()->countryToCapital()->completed()->for($user)->create();
    QuizAttempt::factory()->countryToCapital()->completed()->for($user)->create();
    QuizAttempt::factory()->countryToCapital()->failed()->for($user)->create();

    actingAs($user);

    $response = get('/quiz/attempts?mode=test&outcome=test')->assertOk();

    $attempts = $response->viewData('attempts');

    expect($attempts)->toHaveCount(3);
});
