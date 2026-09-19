<x-layout title="Quiz">
    <h1 class="mb-6 text-2xl font-semibold">Countries & Capitals Quiz</h1>

    <p class="mb-6 text-gray-600">Answer as many questions as you can — one wrong answer or running out of time ends the round.</p>

    <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
        @foreach ($modes as $mode)
            <a
                href="{{ route('quiz.start', $mode) }}"
                class="rounded-md border border-gray-300 bg-white p-6 text-center font-medium hover:border-gray-900"
            >
                {{ $mode->label() }}
            </a>
        @endforeach
    </div>
</x-layout>
