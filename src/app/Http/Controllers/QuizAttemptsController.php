<?php

namespace App\Http\Controllers;

use App\Enums\QuizMode;
use App\Enums\QuizOutcome;
use App\Models\Country;
use App\Models\QuizAnswer;
use App\Models\QuizAttempt;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class QuizAttemptsController extends Controller
{
    public function attempts(Request $request)
    {
        $userId = Auth::id();
        $mode = $request->enum('mode', QuizMode::class);
        $outcome = $request->enum('outcome', QuizOutcome::class);

        $attempts = QuizAttempt::query()
            ->where('user_id', $userId)
            ->when($mode, fn ($q) => $q->where('mode', $mode))
            ->when($outcome, fn ($q) => $q->where('outcome', $outcome))
            ->orderBy('created_at', 'desc')
            ->paginate(25)
            ->withQueryString();

        return view('quiz.attempts', [
            'attempts' => $attempts,
            'modes' => QuizMode::cases(),
            'outcomes' => QuizOutcome::cases(),
        ]);
    }

    public function show(QuizAttempt $attempt)
    {
        $attempt->load('answers');
        $countryIds = $attempt->answers
            ->flatMap(fn (QuizAnswer $answer) => $answer->option_country_ids)
            ->unique();
        $countries = Country::with('capitals')
            ->whereIn('id', $countryIds)
            ->get()
            ->keyBy('id');

        return view('quiz.attempt', [
            'attempt' => $attempt,
            'countries' => $countries,
        ]);
    }
}
