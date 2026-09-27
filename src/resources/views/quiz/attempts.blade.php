<x-layout title="My Attempts">
    <h1 class="mb-6 text-2xl font-semibold">My attempts</h1>

    <form method="GET" class="mb-6 flex flex-col sm:flex-row gap-2">
        <select
            name="mode"
            class="cursor-pointer rounded border border-gray-900 bg-white px-4 py-2"
        >
            <option value="">All modes</option>
            @foreach ($modes as $mode)
                <option
                    value="{{ $mode->value }}"
                    @selected (request('mode') === $mode->value)
                >
                    {{ $mode->label() }}
                </option>
            @endforeach
        </select>
        <select
            name="outcome"
            class="cursor-pointer rounded border border-gray-900 bg-white px-4 py-2"
        >
            <option value="">All outcomes</option>
            @foreach ($outcomes as $outcome)
                <option
                    value="{{ $outcome->value }}"
                    @selected (request('outcome') === $outcome->value)
                >
                    {{ $outcome->shortLabel() }}
                </option>
            @endforeach
        </select>
        <button
            type="submit"
            class="cursor-pointer rounded bg-gray-900 px-4 py-2 text-sm text-white transition-opacity hover:opacity-70"
        >
            Filter
        </button>
    </form>

    @if ($attempts->isEmpty())
        <p class="text-gray-600">No results found.</p>
    @else
        <div class="overflow-x-auto rounded-md border border-gray-300 bg-white">
            <table class="min-w-full divide-y divide-gray-200 text-sm">
                <thead class="bg-gray-50 text-left text-gray-600">
                    <tr>
                        <th class="px-4 py-2">Mode</th>
                        <th class="px-4 py-2">Outcome</th>
                        <th class="px-4 py-2">Score</th>
                        <th class="px-4 py-2">Reached question</th>
                        <th class="px-4 py-2">Date</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-200">
                    @foreach ($attempts as $attempt)
                        <tr>
                            <td class="px-4 py-2">{{ $attempt->mode->label() }}</td>
                            <td class="px-4 py-2">{{ $attempt->outcome->shortLabel() }}</td>
                            <td class="px-4 py-2">{{ $attempt->score }}</td>
                            <td class="px-4 py-2">{{ $attempt->stopped_at_question }}</td>
                            <td class="px-4 py-2">{{ $attempt->created_at->format('M j, Y H:i') }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>

        <div class="mt-6">
            {{ $attempts->links() }}
        </div>
    @endif
</x-layout>
