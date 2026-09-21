<x-layout title="Results">
    <h1 class="mb-4 text-2xl font-semibold">{{ $heading }}</h1>

    <p class="mb-6 text-gray-600">
        Score: {{ $score }} · Reached question {{ $stoppedAtQuestion }}
    </p>

    <div class="flex gap-4">
        <a
            href="{{ route('quiz.landing') }}"
            class="rounded-md bg-gray-900 px-5 py-2 font-medium text-white hover:opacity-90"
        >
            Play again
        </a>
        <a
            href="{{ route('home') }}"
            class="rounded-md border border-gray-300 bg-white px-5 py-2 font-medium hover:border-gray-900"
        >
            Home
        </a>
    </div>
</x-layout>
