<?php

namespace App\Http\Controllers;

use App\Enums\QuizMode;
use Illuminate\Http\Request;

class QuizController extends Controller
{
    public function landing()
    {
        return view('quiz.landing', [
            'modes' => QuizMode::cases(),
        ]);
    }

    public function start(QuizMode $mode)
    {
        // TODO
    }

    public function question()
    {
        // TODO
    }

    public function answer(Request $request)
    {
        // TODO
    }

    public function results()
    {
        // TODO
    }
}
