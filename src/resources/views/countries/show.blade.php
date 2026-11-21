@use ('App\Enums\Membership')
<x-layout title="{{ $country->name_common }}">
    <p class="mb-6">
        <a
            class="border-b-1 border-transparent text-sm hover:border-gray-900 hover:text-gray-900"
            href="{{ route('countries.index') }}"
            >⟵ Back</a
        >
    </p>

    <h1 class="mb-6 text-2xl font-semibold">
        {{ $country->name_common }}
        @if ($country->name_official)
            ({{ $country->name_official }})
        @endif
    </h1>
    @if ($country->descriptions_short)
        <p class="mb-6 font-semibold">{{ $country->descriptions_short }}</p>
    @endif
    <div class="grid grid-cols-1 gap-6 md:grid-cols-2">
        <div
            class="mb-6 w-full rounded-xl border border-gray-200 p-4 shadow-sm sm:p-6"
        >
            @if ($country->flag_url)
                <img
                    src="{{ $country->flag_url }}"
                    alt="Flag of {{ $country->name_common }}"
                    class="mb-2 border border-gray-900"
                />
            @endif
        </div>
        <div>
            @foreach ( $country->capitals as $capital )
                <div
                    class="mb-6 w-full rounded-xl border border-gray-200 p-4 shadow-sm sm:p-6"
                >
                    <h2 class="mb-3 text-lg font-semibold">
                        {{ $capital->name }}
                    </h2>
                    @if ($capital->weather)
                        <dl class="grid grid-cols-2 gap-x-4 gap-y-4">
                            <dt class="text-xs text-gray-500">
                                Temperature (°C)
                            </dt>
                            <dd class="text-xs text-gray-500">
                                {{ $capital->weather->temperature_c }}
                            </dd>
                            <dt class="text-xs text-gray-500">
                                Wind speed (km/h)
                            </dt>
                            <dd class="text-xs text-gray-500">
                                {{ $capital->weather->wind_speed_kmh }}
                            </dd>
                            <dt class="text-xs text-gray-500">Weather</dt>
                            <dd class="text-xs text-gray-500">
                                {{ $capital->weather->weather_code->label() }}
                            </dd>
                            <dt class="text-xs text-gray-500">Day/Night</dt>
                            <dd class="text-xs text-gray-500">
                                {{ $capital->weather->is_day ? 'Day' : 'Night' }}
                            </dd>
                            <dt class="text-xs text-gray-500">observed_at</dt>
                            <dd class="text-xs text-gray-500">
                                {{ $capital->weather->observed_at }}
                            </dd>
                            <dt class="text-xs text-gray-500">updated_at</dt>
                            <dd class="text-xs text-gray-500">
                                {{ $capital->weather->updated_at }}
                            </dd>
                        </dl>
                    @else
                        <p class="text-sm text-gray-500">Weather unavailable</p>
                    @endif
                </div>
            @endforeach
            <div
                class="mb-6 w-full rounded-xl border border-gray-200 p-4 shadow-sm sm:p-6"
            >
                <dl class="grid grid-cols-2 gap-x-3 gap-y-4 sm:gap-x-6">
                    @if (!is_null($country->region))
                        <dt class="text-gray-500">Region</dt>
                        <dd class="font-medium text-gray-900">
                            {{ $country->region }}
                        </dd>
                    @endif
                    @if (!is_null($country->subregion))
                        <dt class="text-gray-500">Subregion</dt>
                        <dd class="font-medium text-gray-900">
                            {{ $country->subregion }}
                        </dd>
                    @endif
                    @if (filled($country->continents))
                        <dt class="text-gray-500">Continents</dt>
                        <dd class="font-medium text-gray-900">
                            {{ implode( ', ', $country->continents) }}
                        </dd>
                    @endif
                    @if ($country->languages->isNotEmpty())
                        <dt class="text-gray-500">Language(s)</dt>
                        <dd class="font-medium text-gray-900">
                            @foreach ($country->languages as $language)
                                <span
                                    class="mr-2 mb-2 inline-block rounded-xl border border-gray-200 p-2"
                                    >{{ $language->name }}</span
                                >
                            @endforeach
                        </dd>
                    @endif
                    @if ($country->cca3)
                        <dt class="text-gray-500">CCA3</dt>
                        <dd class="font-medium text-gray-900">
                            {{ $country->cca3 }}
                        </dd>
                    @endif
                    @if (!is_null($country->population))
                        <dt class="text-gray-500">Population</dt>
                        <dd class="font-medium text-gray-900">
                            {{ Number::format($country->population) }}
                        </dd>
                    @endif
                    @if (!is_null($country->area_km2))
                        <dt class="text-gray-500">Area (km²)</dt>
                        <dd class="font-medium text-gray-900">
                            {{ Number::format((float) $country->area_km2, maxPrecision: 2) }}
                        </dd>
                    @endif
                    @if (!is_null($country->density))
                        <dt class="text-gray-500">Density (/km²)</dt>
                        <dd class="font-medium text-gray-900">
                            {{ Number::format((float) $country->density, maxPrecision: 2) }}
                        </dd>
                    @endif
                    @if (!is_null($country->sovereign))
                        <dt class="text-gray-500">Sovereign</dt>
                        <dd class="font-medium text-gray-900">
                            {{ $country->sovereign ? 'Yes' : 'No' }}
                        </dd>
                    @endif
                    @php
                        $memberships = $country->knownMemberships();
                    @endphp
                    @if ($memberships)
                        <dt class="text-gray-500">Memberships</dt>
                        <dd class="font-medium text-gray-900">
                            @foreach ($memberships as $membership)
                                <span
                                    class="mr-2 mb-2 inline-block rounded-xl border border-gray-200 p-2"
                                    >{{ $membership->label() }}</span
                                >
                            @endforeach
                        </dd>
                    @endif
                </dl>
            </div>
        </div>
    </div>

    <div class="mt-10 grid grid-cols-2 gap-4">
        {{-- Previous --}}
        <div>
            @if ($previous)
                <a
                    href="{{ route('countries.show', $previous) }}"
                    class="group block"
                >
                    <span class="text-sm text-gray-500"> ← Previous </span>

                    <h3 class="mt-2 font-semibold group-hover:text-blue-600">
                        {{ $previous->name_common }}
                    </h3>
                </a>
            @endif
        </div>

        {{-- Next --}}
        <div class="text-right">
            @if ($next)
                <a
                    href="{{ route('countries.show', $next) }}"
                    class="group block"
                >
                    <span class="text-sm text-gray-500"> Next → </span>

                    <h3 class="mt-2 font-semibold group-hover:text-blue-600">
                        {{ $next->name_common }}
                    </h3>
                </a>
            @endif
        </div>
    </div>
</x-layout>
