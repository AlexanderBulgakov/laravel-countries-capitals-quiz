<x-layout title="Quiz">
    <div
        x-data="quizPlay({
            prompt: @js($prompt),
            options: @js($options),
            remainingSeconds: {{ $remainingSeconds }},
            showLoader: @js($showLoader),
            score: {{ $score }},
            questionNumber: {{ $questionNumber }},
            totalQuestions: {{ $totalQuestions }},
            answerUrl: @js(route('quiz.answer')),
            resultsUrl: @js(route('quiz.results')),
        })"
        x-cloak
    >
        <div
            x-show="loading"
            class="flex h-64 items-center justify-center text-gray-400"
        >
            Loading…
        </div>

        <div x-show="!loading">
            <div
                class="mb-4 flex items-center justify-between text-sm text-gray-500"
            >
                <span class="text-red-600"
                    >Time left: <span x-text="secondsLeft"></span>s</span
                >
                <span class="text-gray-500">
                    Question <span x-text="questionNumber"></span>/<span
                        x-text="totalQuestions"
                    ></span>
                </span>
                <span class="text-green-600"
                    >Score: <span x-text="score"></span
                ></span>
            </div>

            <h1 class="mb-6 text-2xl font-semibold" x-text="prompt"></h1>

            <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                <template x-for="option in options" :key="option.id">
                    <button
                        type="button"
                        @click="answer(option.id)"
                        :disabled="answered"
                        class="cursor-pointer rounded-md border p-6 text-center font-medium transition disabled:cursor-not-allowed"
                        :class="optionClass(option.id)"
                        x-text="option.label"
                    ></button>
                </template>
            </div>
        </div>
    </div>
</x-layout>
