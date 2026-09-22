<x-layout title="Home">
    <h1 class="mb-4 text-2xl font-semibold">Countries & Capitals Quiz</h1>

    <p class="mb-6 text-gray-600">Browse the world's countries and capitals, or jump straight into the quiz.</p>

    <div class="flex gap-4">
        <a
            href="{{ route('countries.index') }}"
            class="rounded-md border border-gray-300 bg-white px-5 py-2 font-medium hover:border-gray-900"
        >
            Browse countries
        </a>
        <a
            href="{{ route('quiz.landing') }}"
            class="rounded-md bg-gray-900 px-5 py-2 font-medium text-white hover:opacity-90"
        >
            Play the quiz
        </a>
    </div>
</x-layout>
