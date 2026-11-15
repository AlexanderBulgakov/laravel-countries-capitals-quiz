<?php

use App\Models\Country;
use App\Models\Language;
use Illuminate\Testing\TestResponse;

function countryNamesOnPage(TestResponse $response): array
{
    return $response->viewData('countries')->pluck('name_common')->all();
}

it('lists countries on the countries page', function () {
    Country::factory()->create(['name_common' => 'Testlandia']);

    $response = $this->get('/countries');

    $response->assertOk();
    $response->assertSee('Testlandia');
});

it('filters countries by region', function () {
    Country::factory()->create(['region' => 'Africa', 'name_common' => 'Alpha Country']);
    Country::factory()->create(['region' => 'Europe', 'name_common' => 'Beta Country']);

    $response = $this->get(route('countries.index', ['region' => 'Africa']));

    $response->assertOk();
    expect(countryNamesOnPage($response))
        ->toEqualCanonicalizing(['Alpha Country']);
});

it('filters countries by subregion', function () {
    Country::factory()->create([
        'region' => 'Africa',
        'subregion' => 'Western Africa',
        'name_common' => 'Alpha Country',
    ]);
    Country::factory()->create([
        'region' => 'Europe',
        'subregion' => 'Western Europe',
        'name_common' => 'Beta Country',
    ]);

    $response = $this->get(route('countries.index', ['subregion' => 'Western Africa']));

    $response->assertOk();
    expect(countryNamesOnPage($response))
        ->toEqualCanonicalizing(['Alpha Country']);
});

it('filters countries by sovereign', function (array $query, array $expected) {
    Country::factory()->create([
        'name_common' => 'Cameroon',
        'sovereign' => true,
    ]);
    Country::factory()->create([
        'name_common' => 'Cyprus',
        'sovereign' => false,
    ]);
    Country::factory()->create([
        'name_common' => 'Cambodia',
        'sovereign' => null,
    ]);

    $response = $this->get(route('countries.index', $query));

    $response->assertOk();
    expect(countryNamesOnPage($response))
        ->toEqualCanonicalizing($expected);
})->with([
    'sovereign=1' => [['sovereign' => '1'], ['Cameroon']],
    'sovereign=0' => [['sovereign' => '0'], ['Cyprus']],
    'sovereign=' => [['sovereign' => ''], ['Cambodia', 'Cameroon', 'Cyprus']],
]);

it('filters countries by memberships', function (array $query, array $expected) {
    Country::factory()->create(['name_common' => 'Cameroon', 'memberships' => ['african_union', 'un']]);
    Country::factory()->create(['name_common' => 'Cyprus', 'memberships' => []]);
    Country::factory()->create(['name_common' => 'Cambodia', 'memberships' => ['asean', 'un']]);

    $response = $this->get(route('countries.index', $query));

    $response->assertOk();
    expect(countryNamesOnPage($response))
        ->toEqualCanonicalizing($expected);
})->with([
    'one membership' => [['memberships' => ['un']], ['Cameroon', 'Cambodia']],
    'two memberships mean AND' => [['memberships' => ['african_union', 'un']], ['Cameroon']],
    'empty value means no filter' => [['memberships' => ['']], ['Cameroon', 'Cyprus', 'Cambodia']],
]);

it('filters countries by language', function (array $query, array $expected) {
    $canada = Country::factory()->create([
        'name_common' => 'Canada',
    ]);

    $uk = Country::factory()->create([
        'name_common' => 'United Kingdom',
    ]);

    Country::factory()->create([
        'name_common' => 'Antarctica',
    ]);

    $languageEng = Language::create(
        [
            'name' => 'English',
            'iso639_3' => 'eng',
        ],
    );
    $languageFra = Language::create(
        [
            'name' => 'French',
            'iso639_3' => 'fra',
        ],
    );

    $canada->languages()->attach([$languageEng->id, $languageFra->id]);
    $uk->languages()->attach([$languageEng->id]);

    $response = $this->get(route('countries.index', $query));

    $response->assertOk();
    expect(countryNamesOnPage($response))
        ->toEqualCanonicalizing($expected);
})->with([
    'only eng' => [['language' => 'eng'], ['Canada', 'United Kingdom']],
    'only fra' => [['language' => 'fra'], ['Canada']],
    'unknown code' => [['language' => '123'], []],
    'empty value' => [['language' => ''], ['Antarctica', 'Canada', 'United Kingdom']],
]);

it('filters countries by population', function (array $query, array $expected) {
    Country::factory()->create([
        'name_common' => 'Country100',
        'population' => 100,
    ]);

    Country::factory()->create([
        'name_common' => 'Country500',
        'population' => 500,
    ]);

    Country::factory()->create([
        'name_common' => 'Country1000',
        'population' => 1000,
    ]);

    Country::factory()->create([
        'name_common' => 'Country0',
        'population' => 0,
    ]);

    $response = $this->get(route('countries.index', $query));

    $response->assertOk();
    expect(countryNamesOnPage($response))
        ->toEqualCanonicalizing($expected);
})->with([
    'only min' => [['population_min' => '500'], ['Country500', 'Country1000']],
    'only max' => [['population_max' => '500'], ['Country0', 'Country500', 'Country100']],
    'min > max' => [['population_min' => '1500', 'population_max' => '500'], []],
    'min == max' => [['population_min' => '500', 'population_max' => '500'], ['Country500']],
    'max = 0' => [['population_max' => '0'], ['Country0']],
    'no filter' => [[], ['Country0', 'Country100', 'Country500', 'Country1000']],
]);

it('filters countries by area', function (array $query, array $expected) {
    Country::factory()->create([
        'name_common' => 'Country100',
        'area_km2' => 1000,
    ]);

    Country::factory()->create([
        'name_common' => 'Country500',
        'area_km2' => 16000,
    ]);

    Country::factory()->create([
        'name_common' => 'Country1000',
        'area_km2' => null,
    ]);

    $response = $this->get(route('countries.index', $query));

    $response->assertOk();
    expect(countryNamesOnPage($response))
        ->toEqualCanonicalizing($expected);
})->with([
    'only min' => [['area_min' => '16000'], ['Country500']],
    'only max' => [['area_max' => '16000'], ['Country100', 'Country500']],
    'min > max' => [['area_min' => '100500', 'area_max' => '500'], []],
    'min == max' => [['area_min' => '16000', 'area_max' => '16000'], ['Country500']],
    'max = 0' => [['area_max' => '0'], []],
    'no filter' => [[], ['Country100', 'Country500', 'Country1000']],
]);

it('filters countries by density', function (array $query, array $expected) {
    Country::factory()->create([
        'name_common' => 'Sparse',
        'population' => 1000,
        'area_km2' => 10000,
    ]);

    Country::factory()->create([
        'name_common' => 'Dense',
        'population' => 5000,
        'area_km2' => 1000,
    ]);

    Country::factory()->create([
        'name_common' => 'ZeroArea',
        'population' => 1000,
        'area_km2' => 0,
    ]);

    $response = $this->get(route('countries.index', $query));

    $response->assertOk();
    expect(countryNamesOnPage($response))
        ->toEqualCanonicalizing($expected);
})->with([
    'only min' => [['density_min' => '5'], ['Dense']],
    'only max' => [['density_max' => '5'], ['Sparse', 'Dense']],
    'min > max' => [['density_min' => '500', 'density_max' => '5'], []],
    'min == max' => [['density_min' => '5', 'density_max' => '5'], ['Dense']],
    'max = 0' => [['density_max' => '0'], []],
    'no filter' => [[], ['Sparse', 'Dense', 'ZeroArea']],
]);

it('combines filters with AND', function (array $query, array $expected) {
    Country::factory()->create([
        'region' => 'Region1',
        'name_common' => 'Country1',
        'sovereign' => true,
        'population' => 1000,
    ]);

    Country::factory()->create([
        'region' => 'Region2',
        'name_common' => 'Country2',
        'sovereign' => false,
        'population' => 2000,
    ]);

    Country::factory()->create([
        'region' => 'Region1',
        'name_common' => 'Country3',
        'sovereign' => true,
        'population' => 2000,
    ]);

    $response = $this->get(route('countries.index', $query));

    $response->assertOk();
    expect(countryNamesOnPage($response))
        ->toEqualCanonicalizing($expected);
})->with([
    'region=yes sovereign=no' => [['region' => 'Region1', 'sovereign' => '0'], []],
    'sovereign=yes population=no' => [['sovereign' => '1', 'population_min' => '5000'], []],
    'region=yes sovereign=yes population=yes' => [['region' => 'Region1', 'sovereign' => '1', 'population_max' => '1000'], ['Country1']],
]);

it('lists languages ordered by country count', function () {
    $canada = Country::factory()->create([
        'name_common' => 'Canada',
    ]);

    $france = Country::factory()->create([
        'name_common' => 'France',
    ]);

    $languageEng = Language::create(
        [
            'name' => 'English',
            'iso639_3' => 'eng',
        ],
    );
    $languageFra = Language::create(
        [
            'name' => 'French',
            'iso639_3' => 'fra',
        ],
    );
    Language::create(
        [
            'name' => 'Spanish',
            'iso639_3' => 'spa',
        ],
    );

    $canada->languages()->attach([$languageEng->id, $languageFra->id]);
    $france->languages()->attach([$languageFra->id]);

    $response = $this->get(route('countries.index'));

    $response->assertOk();
    expect($response->viewData('languages')->pluck('iso639_3')->all())
        ->toBe(['fra', 'eng']);
});

it('groups subregions by region', function () {
    Country::factory()->create([
        'name_common' => 'Country 1',
        'region' => 'Africa',
        'subregion' => 'Western Africa',
    ]);

    Country::factory()->create([
        'name_common' => 'Country 2',
        'region' => 'Africa',
        'subregion' => 'Eastern Africa',
    ]);

    Country::factory()->create([
        'name_common' => 'Country 3',
        'region' => 'Europe',
        'subregion' => 'Western Europe',
    ]);

    Country::factory()->create([
        'name_common' => 'Country 4',
        'region' => 'Europe',
        'subregion' => null,
    ]);

    Country::factory()->create([
        'name_common' => 'Country 5',
        'region' => 'Africa',
        'subregion' => 'Western Africa',
    ]);

    $response = $this->get(route('countries.index'));
    $subregions = $response->viewData('subregions');

    $response->assertOk();
    expect($subregions->get('Africa')->pluck('subregion')->all())
        ->toBe(['Eastern Africa', 'Western Africa']);
    expect($subregions->get('Europe')->pluck('subregion')->all())
        ->toBe(['Western Europe']);
    expect($subregions->keys()->all())->toBe(['Africa', 'Europe']);
});

it('renders the page with all filters applied', function () {
    $france = Country::factory()->create([
        'name_common' => 'Testland',
        'region' => 'Test',
        'subregion' => 'Test',
        'population' => 1,
        'area_km2' => 1,
        'sovereign' => true,
        'memberships' => ['african_union', 'un'],
    ]);

    $languageFra = Language::create(
        [
            'name' => 'French',
            'iso639_3' => 'fra',
        ],
    );

    $france->languages()->attach([$languageFra->id]);

    $response = $this->get(route('countries.index', [
        'region' => 'Test',
        'subregion' => 'Test',
        'population_min' => '1',
        'population_max' => '2',
        'area_min' => '1',
        'area_max' => '2',
        'density_min' => '1',
        'density_max' => '2',
        'sovereign' => '1',
        'language' => 'fra',
        'memberships' => ['african_union', 'un'],
    ]));

    $response->assertOk();
    expect(countryNamesOnPage($response))
        ->toBe(['Testland']);
});
