<x-layout title="Countries">
    <h1 class="mb-6 text-2xl font-semibold">Countries</h1>

    <form method="GET" class="mb-6 flex gap-2">
        <select
            name="region"
            class="cursor-pointer rounded border border-gray-900 bg-white px-4 py-2"
        >
            <option value="">All regions</option>
            @foreach ($regions as $region)
                <option
                    value="{{ $region }}"
                    @selected (request('region') === $region)
                >
                    {{ $region }}
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

    <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-3">
        @foreach ($countries as $country)
            <div class="rounded-md border border-gray-300 bg-white p-4">
                @if ($country->flag_url)
                    <img
                        src="{{ $country->flag_url }}"
                        alt="Flag of {{ $country->name_common }}"
                        class="mb-2 h-8 border border-gray-900"
                    />
                @endif
                <div class="font-medium">{{ $country->name_common }}</div>
                <div class="text-sm text-gray-500">
                    <strong>Region:</strong> {{ $country->region }}
                </div>
                <div class="text-sm text-gray-500">
                    <strong>Capital(s):</strong>
                    {{ $country->capitals->pluck('name')->join(', ') ?: '—' }}
                </div>
            </div>
        @endforeach
    </div>

    <div class="mt-6">{{ $countries->links() }}</div>
</x-layout>
