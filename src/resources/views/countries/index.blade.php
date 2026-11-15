@use ('App\Enums\Membership')
<x-layout title="Countries">
    <h1 class="mb-6 text-2xl font-semibold">Countries</h1>
    <div class="grid grid-cols-1 gap-4 md:grid-cols-[280px_1fr]">
        <aside>
            <form method="GET" class="mb-6 flex flex-col gap-2">
                <a
                    href="{{ route('countries.index') }}"
                    class="cursor-pointer rounded bg-gray-900 px-4 py-2 text-center text-sm text-white transition-opacity hover:opacity-70"
                    >Reset</a
                >

                <label for="region" class="font-semibold">Region</label>
                <select
                    id="region"
                    name="region"
                    class="cursor-pointer rounded border border-gray-900 bg-white px-4 py-2"
                >
                    <option value="">All regions</option>
                    @foreach ($regions as $region)
                        <option
                            value="{{ $region }}"
                            @selected ($filters->region === $region)
                        >
                            {{ $region }}
                        </option>
                    @endforeach
                </select>

                <label for="subregion" class="font-semibold">Subregion</label>
                <select
                    id="subregion"
                    name="subregion"
                    class="cursor-pointer rounded border border-gray-900 bg-white px-4 py-2"
                >
                    <option value="">All</option>
                    @foreach ($subregions as $region => $items)
                        <optgroup label="{{ $region }}">
                            @foreach ($items as $item)
                                <option
                                    value="{{ $item->subregion }}"
                                    @selected ($filters->subregion === $item->subregion)
                                >
                                    {{ $item->subregion }}
                                </option>
                            @endforeach
                        </optgroup>
                    @endforeach
                </select>

                <details @if ($filters->hasAdvancedFilters()) open @endif>
                    <summary class="mb-2 cursor-pointer">More filters</summary>

                    <div class="mb-2 flex flex-col gap-2">
                        <label for="language" class="font-semibold">Language</label>
                        <select
                            id="language"
                            name="language"
                            class="w-full cursor-pointer rounded border border-gray-900 bg-white px-4 py-2"
                        >
                            <option value="">Any</option>
                            @foreach ($languages as $language)
                                <option
                                    value="{{ $language->iso639_3 }}"
                                    @selected ($filters->language === $language->iso639_3)
                                >
                                    {{ $language->name }} ({{ $language->countries_count }})
                                </option>
                            @endforeach
                        </select>

                        <label for="sovereign" class="font-semibold"
                            >Sovereign</label
                        >
                        <select
                            id="sovereign"
                            name="sovereign"
                            class="w-full cursor-pointer rounded border border-gray-900 bg-white px-4 py-2"
                        >
                            <option value="">Any</option>
                            <option
                                value="1"
                                @selected ($filters->sovereign === true)
                            >
                                Sovereign
                            </option>
                            <option
                                value="0"
                                @selected ($filters->sovereign === false)
                            >
                                Non-sovereign
                            </option>
                        </select>

                        <fieldset>
                            <legend class="font-semibold">Population</legend>
                            <label>
                                Min
                                <input
                                    class="w-full"
                                    name="population_min"
                                    type="number"
                                    value="{{ $filters->populationMin }}"
                                    min="0"
                                    step="1"
                                />
                            </label>
                            <label>
                                Max
                                <input
                                    class="w-full"
                                    name="population_max"
                                    type="number"
                                    value="{{ $filters->populationMax }}"
                                    min="0"
                                    step="1"
                                />
                            </label>
                        </fieldset>

                        <fieldset>
                            <legend class="font-semibold">Area</legend>
                            <label>
                                Min
                                <input
                                    class="w-full"
                                    step="any"
                                    name="area_min"
                                    type="number"
                                    value="{{ $filters->areaMin }}"
                                    min="0"
                                />
                            </label>
                            <label>
                                Max
                                <input
                                    class="w-full"
                                    step="any"
                                    name="area_max"
                                    type="number"
                                    value="{{ $filters->areaMax }}"
                                    min="0"
                                />
                            </label>
                        </fieldset>

                        <fieldset>
                            <legend class="font-semibold">Density</legend>
                            <label>
                                Min
                                <input
                                    class="w-full"
                                    step="any"
                                    name="density_min"
                                    type="number"
                                    value="{{ $filters->densityMin }}"
                                    min="0"
                                />
                            </label>
                            <label>
                                Max
                                <input
                                    class="w-full"
                                    step="any"
                                    name="density_max"
                                    type="number"
                                    value="{{ $filters->densityMax }}"
                                    min="0"
                                />
                            </label>
                        </fieldset>

                        <fieldset>
                            <legend class="font-semibold">Memberships</legend>
                            @foreach (Membership::cases() as $membership)
                                <label class="cursor-poiner flex items-center gap-2">
                                    <input
                                        type="checkbox"
                                        name="memberships[]"
                                        @checked (in_array($membership, $filters->memberships, true))
                                        value="{{ $membership->value }}"
                                    />
                                    <span>{{ $membership->label() }}</span>
                                </label>
                            @endforeach
                        </fieldset>
                    </div>
                </details>

                <button
                    type="submit"
                    class="cursor-pointer rounded bg-gray-900 px-4 py-2 text-sm text-white transition-opacity hover:opacity-70"
                >
                    Filter
                </button>
            </form>
        </aside>

        <div>
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
                        <div class="font-medium">
                            {{ $country->name_common }}
                        </div>
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
        </div>
    </div>
</x-layout>
