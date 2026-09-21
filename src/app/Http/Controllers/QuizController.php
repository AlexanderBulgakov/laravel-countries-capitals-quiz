<?php

namespace App\Http\Controllers;

use App\Enums\QuizMode;
use App\Enums\QuizOutcome;
use App\Models\Country;
use App\Services\Quiz\GeneratedQuestion;
use App\Services\Quiz\GuestQuizSession;
use Illuminate\Http\Request;

class QuizController extends Controller
{
    public function landing()
    {
        return view('quiz.landing', [
            'modes' => QuizMode::cases(),
        ]);
    }

    public function start(QuizMode $mode, GuestQuizSession $quizSession)
    {
        $current = $quizSession->current();

        $question = ($current && $current->mode === $mode)
            ? $current
            : $quizSession->start($mode);

        return view('quiz.play', $this->present($question));
    }

    public function answer(Request $request, GuestQuizSession $quizSession)
    {
        if (! $quizSession->current()) {
            return response()->json(['error' => 'no_active_quiz'], 409);
        }

        $optionId = $request->filled('option_id') ? $request->integer('option_id') : null;

        $result = $quizSession->submitAnswer($optionId);

        return response()->json([
            'correct' => $result->correct,
            'correct_country_id' => $result->correctCountryId,
            'score' => $result->score,
            'finished' => $result->finished,
            'outcome' => $result->outcome?->value,
            'next_question' => $result->nextQuestion ? $this->present($result->nextQuestion) : null,
        ]);
    }

    public function results(GuestQuizSession $quizSession)
    {
        $lastResult = $quizSession->lastResult();

        if (! $lastResult) {
            return redirect()->route('quiz.landing');
        }

        $outcome = QuizOutcome::from($lastResult['outcome']);

        return view('quiz.results', [
            'heading' => match ($outcome) {
                QuizOutcome::Completed => 'You completed the quiz!',
                QuizOutcome::Failed => 'Wrong answer — game over.',
                QuizOutcome::Timeout => "Time's up — game over.",
            },
            'score' => $lastResult['score'],
            'stoppedAtQuestion' => $lastResult['stopped_at_question'],
        ]);
    }

    private function present(GeneratedQuestion $question): array
    {
        $countries = Country::query()
            ->with('capitals')
            ->whereIn('id', $question->optionCountryIds)
            ->get()
            ->keyBy('id');

        $target = $countries[$question->targetCountryId];

        return [
            'prompt' => match ($question->mode) {
                QuizMode::CountryToCapital => $target->name_common,
                QuizMode::CapitalToCountry => $target->capitals->first()->name,
            },
            'options' => collect($question->optionCountryIds)->map(fn ($id) => [
                'id' => $id,
                'label' => match ($question->mode) {
                    QuizMode::CountryToCapital => $countries[$id]->capitals->first()->name,
                    QuizMode::CapitalToCountry => $countries[$id]->name_common,
                },
            ]),
        ];
    }
}
