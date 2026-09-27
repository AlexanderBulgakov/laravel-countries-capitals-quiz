<x-layout title="Leaderboard">
    <h1 class="mb-6 text-2xl font-semibold">Leaderboard</h1>

    <div class="overflow-x-auto rounded-md border border-gray-300 bg-white">
        <table class="min-w-full divide-y divide-gray-200 text-sm">
            <thead class="bg-gray-50 text-left text-gray-600">
                <tr>
                    <th class="px-4 py-2">Rank</th>
                    <th class="px-4 py-2">User</th>
                    <th class="px-4 py-2">Completed quizzes</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-200">
                @foreach ($rankings as $data)
                    <tr>
                        <td class="px-4 py-2">{{ $data->rank }}</td>
                        <td class="px-4 py-2">{{ $data->name }}</td>
                        <td class="px-4 py-2">{{ $data->completed_count }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>

    <div class="mt-6">
        {{ $rankings->links() }}
    </div>
</x-layout>
