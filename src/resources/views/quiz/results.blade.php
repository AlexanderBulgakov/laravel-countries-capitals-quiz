<x-layout title="Results">
    <h1 class="mb-4 text-2xl font-semibold">{{ $heading }}</h1>

    <p class="mb-6 text-gray-600">
        Score: {{ $score }}<br />Reached question: {{ $stoppedAtQuestion }}
    </p>

    <div>
        <a
            href="{{ route('quiz.start', $mode) }}"
            class="rounded-md bg-gray-900 px-5 py-2 font-medium text-white hover:opacity-90"
        >
            Play again
        </a>
    </div>
</x-layout>
