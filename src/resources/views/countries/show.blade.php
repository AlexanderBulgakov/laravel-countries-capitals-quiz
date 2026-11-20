<x-layout title="{{ $country->name_common }}">
    <h1 class="mb-6 text-2xl font-semibold">{{ $country->name_common }}</h1>
    <div>
        @foreach ($country->capitals as $capital)
            <p>{{ $capital->name }}</p>
        @endforeach
    </div>
</x-layout>
