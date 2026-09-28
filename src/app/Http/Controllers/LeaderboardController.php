<?php

namespace App\Http\Controllers;

use App\Enums\QuizOutcome;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

class LeaderboardController extends Controller
{
    public function index()
    {
        $rows = Cache::tags(['leaderboard'])->remember(
            'rankings',
            now()->addMinutes(60),
            function () {
                return DB::table('users')
                    ->join('quiz_attempts', fn ($join) => $join
                        ->on('quiz_attempts.user_id', '=', 'users.id')
                        ->where('quiz_attempts.outcome', QuizOutcome::Completed->value))
                    ->select('users.id', 'users.name')
                    ->selectRaw('COUNT(quiz_attempts.id) as completed_count')
                    ->selectRaw('DENSE_RANK() OVER (ORDER BY COUNT(quiz_attempts.id) DESC) as rank')
                    ->groupBy('users.id')
                    ->orderByDesc('completed_count')
                    ->get()
                    ->map(fn ($row) => (array) $row)
                    ->all();
            }
        );

        $top = collect($rows)->filter(fn ($row) => $row['rank'] <= 100)->values()->all();

        $userRank = auth()->check()
                    ? collect($rows)->firstWhere('id', auth()->id())
                    : null;

        return view('leaderboard.index', compact('top', 'userRank'));
    }
}
