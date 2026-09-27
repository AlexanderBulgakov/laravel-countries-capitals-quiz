<?php

namespace App\Http\Controllers;

use App\Enums\QuizOutcome;
use Illuminate\Support\Facades\DB;

class LeaderboardController extends Controller
{
    public function index()
    {
        $rankings = DB::table('users')
            ->leftJoin('quiz_attempts', function ($join) {
                $join->on('quiz_attempts.user_id', '=', 'users.id')
                    ->where('quiz_attempts.outcome', QuizOutcome::Completed->value);
            })
            ->select('users.id', 'users.name')
            ->selectRaw('COUNT(quiz_attempts.id) as completed_count')
            ->selectRaw('DENSE_RANK() OVER (ORDER BY COUNT(quiz_attempts.id) DESC) as rank')
            ->groupBy('users.id')
            ->orderByDesc('completed_count')
            ->paginate(25);

        return view('leaderboard.index', compact('rankings'));
    }
}
