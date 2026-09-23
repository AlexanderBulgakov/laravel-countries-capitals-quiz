<?php

use App\Http\Controllers\CountryController;
use App\Http\Controllers\QuizController;
use App\Http\Controllers\ProfileController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
})->name('home');

Route::get('/countries', [CountryController::class, 'index'])->name('countries.index');

Route::prefix('quiz')->name('quiz.')->group(function () {
    Route::get('/', [QuizController::class, 'landing'])->name('landing');
    Route::get('/results', [QuizController::class, 'results'])->name('results');
    Route::post('/answer', [QuizController::class, 'answer'])->name('answer');
    Route::get('/{mode}', [QuizController::class, 'start'])->name('start');
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
