<?php

use App\Http\Controllers\CountryController;
use App\Http\Controllers\LeaderboardController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\QuizAttemptsController;
use App\Http\Controllers\QuizController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
})->name('home');

Route::get('/countries', [CountryController::class, 'index'])->name('countries.index');

Route::get('/leaderboard', [LeaderboardController::class, 'index'])->name('leaderboard.index');

Route::prefix('quiz')->name('quiz.')->group(function () {
    Route::get('/', [QuizController::class, 'landing'])
        ->name('landing');
    Route::get('/results', [QuizController::class, 'results'])
        ->name('results');
    // block(): serializes concurrent requests on the same session
    // otherwise a double click reads stale state twice and the last write wins.
    Route::post('/answer', [QuizController::class, 'answer'])
        ->block()
        ->name('answer');
    Route::post('/lifelines/{lifeline}', [QuizController::class, 'lifeline'])
        ->block()
        ->name('lifeline');
    Route::get('/attempts', [QuizAttemptsController::class, 'attempts'])
        ->middleware('auth')
        ->name('attempts');
    Route::get('/attempts/{attempt}', [QuizAttemptsController::class, 'show'])
        ->middleware('auth')
        ->can('view', 'attempt')
        ->name('attempts.show');
    Route::get('/{mode}', [QuizController::class, 'start'])
        ->name('start');
});

Route::get('/dashboard', function () {
    return view('dashboard');
})->middleware(['auth', 'verified'])->name('dashboard');

Route::middleware('auth')->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
});

require __DIR__.'/auth.php';
