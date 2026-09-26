<?php

namespace App\Http\Controllers;

use App\Enums\QuizMode;
use App\Enums\QuizOutcome;
use App\Events\QuizAttemptFinished;
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
        // Checks mode too, not just presence — otherwise switching modes
        // mid-session would wrongly resume the previous mode's question.
        $resuming = $current && $current->mode === $mode;
        $question = $resuming ? $current : $quizSession->start($mode);

        return view('quiz.play', [
            ...$this->present($question),
            'remainingSeconds' => $quizSession->remainingSeconds(),
            // Loader compensates for REVEAL_DELAY_SECONDS (see GuestQuizSession)
            // only on the very first reveal — on resume that delay has already
            // played out before the page reload.
            'showLoader' => ! $resuming,
            'score' => $quizSession->score(),
            'questionNumber' => $quizSession->questionNumber(),
            'totalQuestions' => $quizSession->totalQuestions(),
        ]);
    }

    public function answer(Request $request, GuestQuizSession $quizSession)
    {
        if (! $quizSession->current()) {
            return response()->json(['error' => 'no_active_quiz'], 409);
        }

        // filled(), not integer() directly — integer() on a missing key
        // silently returns 0 (a fake country id), while null is the specific
        // signal submitAnswer() expects for "ran out of time".
        $optionId = $request->filled('option_id') ? $request->integer('option_id') : null;

        $result = $quizSession->submitAnswer($optionId);

        if ($result->finished && auth()->check()) {
            $lastResult = $quizSession->lastResult();

            QuizAttemptFinished::dispatch(
                auth()->user(),
                QuizMode::from($lastResult['mode']),
                QuizOutcome::from($lastResult['outcome']),
                $lastResult['score'],
                $lastResult['stopped_at_question'],
            );
        }

        return response()->json([
            'correct' => $result->correct,
            'correct_country_id' => $result->correctCountryId,
            'score' => $result->score,
            'finished' => $result->finished,
            'outcome' => $result->outcome?->value,
            'next_question' => $result->nextQuestion ? $this->present($result->nextQuestion) : null,
            // null when finished — "time remaining" is meaningless once the
            // game is over. question_number below is NOT nulled the same way —
            // "which question you were on" stays a meaningful fact even after
            // losing.
            'remaining_seconds' => $result->finished ? null : $quizSession->remainingSeconds(),
            'question_number' => $quizSession->questionNumber(),
        ]);
    }

    public function results(GuestQuizSession $quizSession)
    {
        $lastResult = $quizSession->lastResult();

        if (! $lastResult) {
            return redirect()->route('quiz.landing');
        }

        $outcome = QuizOutcome::from($lastResult['outcome']);
        $mode = QuizMode::from($lastResult['mode']);

        return view('quiz.results', [
            'heading' => $outcome->label(),
            'score' => $lastResult['score'],
            'stoppedAtQuestion' => $lastResult['stopped_at_question'],
            'mode' => $mode,
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
                    // TODO: always picks the country's first capital (e.g. always Pretoria
                    // for South Africa, never Cape Town/Bloemfontein) — a deliberate
                    // simplification. Fix plan (pick one random capital id once at
                    // generation time).
                    QuizMode::CountryToCapital => $countries[$id]->capitals->first()->name,
                    QuizMode::CapitalToCountry => $countries[$id]->name_common,
                },
            ]),
        ];
    }
}
