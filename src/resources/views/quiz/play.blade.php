<x-layout title="Quiz">
    <h1 class="mb-6 text-2xl font-semibold">{{ $prompt }}</h1>

    <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
        @foreach ($options as $option)
            <button
                type="button"
                data-country-id="{{ $option['id'] }}"
                class="cursor-pointer rounded-md border border-gray-300 bg-white p-6 text-center font-medium hover:border-gray-900"
            >
                {{ $option['label'] }}
            </button>
        @endforeach
    </div>
</x-layout>
