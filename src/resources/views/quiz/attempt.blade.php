@use('App\Enums\QuizOutcome')
<x-layout title="Attempt">
    <p class="mb-6">
        <a class="text-sm border-b-1 border-transparent hover:text-gray-900 hover:border-gray-900" href="{{ route('quiz.attempts') }}">⟵ Back</a>
    </p>

    <h1 class="mb-6 text-2xl font-semibold">Result: {{ $attempt->outcome->shortLabel() }}</h1>

    @if ($attempt->answers->isEmpty())
        <p class="text-gray-600">Details are not available for this attempt.</p>
    @else
        @foreach ($attempt->answers as $answer)
            <div class="mb-6">
                <h2 class="mb-6 font-semibold">Question {{ $answer->question_number }}: {{ $attempt->mode->promptLabel($countries[$answer->target_country_id]) }}</h2>
                <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                    @foreach ($answer->option_country_ids as $id)
                        <div
                            @class([
                                'rounded-md border p-6 text-center font-medium',
                                'border-green-600 bg-green-50' => $answer->target_country_id === $id,
                                'border-2 !border-blue-600' => $answer->chosen_country_id === $id,
                                'bg-red-50' => $answer->chosen_country_id === $id && $answer->target_country_id !== $id,
                            ])
                        >{{ $attempt->mode->optionLabel($countries[$id]) }}</div>
                    @endforeach
                </div>
                @if (is_null($answer->chosen_country_id))
                    <p class="mt-2 text-sm text-red-600">{{ $attempt->outcome === QuizOutcome::Timeout ? 'No answer — time ran out.' : 'No answer.' }}</p>
                @else
                    <p class="mt-2 text-sm">Your answer: {{ $attempt->mode->optionLabel($countries[$answer->chosen_country_id]) }} — {{ $answer->isCorrect() ? 'correct' : 'wrong' }}.</p>
                @endif
            </div>
        @endforeach
    @endif
</x-layout>
