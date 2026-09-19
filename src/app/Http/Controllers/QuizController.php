<?php

namespace App\Http\Controllers;

use App\Enums\QuizMode;
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
