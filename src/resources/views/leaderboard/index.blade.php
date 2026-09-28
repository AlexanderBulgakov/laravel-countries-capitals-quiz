<x-layout title="Leaderboard">
    <h1 class="mb-6 text-2xl font-semibold">Top 100 Leaderboard</h1>

    <div class="overflow-x-auto rounded-md border border-gray-300 bg-white">
        @if ($userRank)
            <div class="border-b border-gray-300 px-4 py-2 text-sm">
                Your position:
                <span class="font-semibold">#{{ $userRank['rank'] }}</span>
                — {{ $userRank['completed_count'] }} completed {{ Str::plural('quiz', $userRank['completed_count']) }}
            </div>
        @endif
        <table class="min-w-full divide-y divide-gray-200 text-sm">
            <thead class="bg-gray-50 text-left text-gray-600">
                <tr>
                    <th class="px-4 py-2">Rank</th>
                    <th class="px-4 py-2">User</th>
                    <th class="px-4 py-2">Completed quizzes</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-200">
                @foreach ($top as $data)
                    <tr
                        class="{{ $userRank && $data['id'] === $userRank['id'] ? 'font-semibold' : '' }}"
                    >
                        <td class="px-4 py-2">{{ $data['rank'] }}</td>
                        <td class="px-4 py-2">{{ $data['name'] }}</td>
                        <td class="px-4 py-2">
                            {{ $data['completed_count'] }}
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>
</x-layout>
