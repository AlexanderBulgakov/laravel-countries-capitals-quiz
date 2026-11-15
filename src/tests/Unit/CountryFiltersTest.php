<?php

use App\Enums\Membership;
use App\Services\Country\CountryFilters;
use Illuminate\Http\Request;

it('parses a fully filled query into typed filters', function () {
    $request = Request::create('/countries', 'GET', [
        'region' => 'Europe',
        'subregion' => 'Southeast Europe',
        'population_min' => '1000',
        'population_max' => '10000',
        'area_min' => '20.02',
        'area_max' => '500.01',
        'density_min' => '19.95',
        'density_max' => '59.99',
        'sovereign' => '1',
        'language' => 'eng',
        'memberships' => ['nato', 'g7'],
    ]);

    $dto = CountryFilters::fromRequest($request);

    expect($dto->region)->toBe('Europe')
        ->and($dto->subregion)->toBe('Southeast Europe')
        ->and($dto->populationMin)->toBe(1_000)
        ->and($dto->populationMax)->toBe(10_000)
        ->and($dto->areaMin)->toBe(20.02)
        ->and($dto->areaMax)->toBe(500.01)
        ->and($dto->densityMin)->toBe(19.95)
        ->and($dto->densityMax)->toBe(59.99)
        ->and($dto->sovereign)->toBeTrue()
        ->and($dto->language)->toBe('eng')
        ->and($dto->memberships)->toBe([Membership::Nato, Membership::G7]);
});

it('treats an empty query as no filters', function () {
    $request = Request::create('/countries', 'GET', []);

    $dto = CountryFilters::fromRequest($request);

    expect($dto->region)->toBeNull()
        ->and($dto->subregion)->toBeNull()
        ->and($dto->populationMin)->toBeNull()
        ->and($dto->populationMax)->toBeNull()
        ->and($dto->areaMin)->toBeNull()
        ->and($dto->areaMax)->toBeNull()
        ->and($dto->densityMin)->toBeNull()
        ->and($dto->densityMax)->toBeNull()
        ->and($dto->sovereign)->toBeNull()
        ->and($dto->language)->toBeNull()
        ->and($dto->memberships)->toBe([]);
});

it('parses a query parameter into a typed filter', function (array $query, string $field, mixed $expected) {
    $filters = CountryFilters::fromRequest(Request::create('/countries', 'GET', $query));

    expect($filters->{$field})->toBe($expected);
})->with([
    'empty region' => [['region' => ''], 'region', null],
    'region with space' => [['region' => ' '], 'region', null],
    'zero population is a valid bound' => [['population_min' => '0'], 'populationMin', 0],
    'negative population is ignored' => [['population_min' => '-5'], 'populationMin', null],
    'array population is ignored' => [['population_min' => ['5']], 'populationMin', null],
    'sovereign 0 means false' => [['sovereign' => '0'], 'sovereign', false],
    'sovereign 1 means true' => [['sovereign' => '1'], 'sovereign', true],
    'sovereign empty means null' => [['sovereign' => ''], 'sovereign', null],
    'sovereign true means null' => [['sovereign' => 'true'], 'sovereign', null],
    'sovereign abc means null' => [['sovereign' => 'abs'], 'sovereign', null],
    'area_min 0 means 0.0' => [['area_min' => '0'], 'areaMin', 0.0],
    'negative area is ignored' => [['area_max' => '-1'], 'areaMax', null],
    'non-numeric density is ignored' => [['density_max' => 'abc'], 'densityMax', null],
    'region as array is ignored' => [['region' => ['Europe']], 'region', null],
    'language is lowercased' => [['language' => 'FRA'], 'language', 'fra'],
    'membership as a single string' => [['memberships' => 'nato'], 'memberships', [Membership::Nato]],
    'unknown membership is dropped and list reindexed' => [['memberships' => ['xxx', 'nato']], 'memberships', [Membership::Nato]],
    'only unknown memberships give no filter' => [['memberships' => ['xxx']], 'memberships', []],
    'duplicate memberships are collapsed' => [['memberships' => ['nato', 'nato']], 'memberships', [Membership::Nato]],
    'nested array membership is ignored' => [['memberships' => [['nato']]], 'memberships', []],
    'membership order is preserved' => [['memberships' => ['g7', 'nato']], 'memberships', [Membership::G7, Membership::Nato]],
]);

it('detects active advanced filters', function (array $query, bool $expected) {
    $filters = CountryFilters::fromRequest(Request::create('/countries', 'GET', $query));

    expect($filters->hasAdvancedFilters())->toBe($expected);
})->with([
    'zero population is an active filter' => [['population_min' => '0'], true],
    'selected sovereign=0' => [['sovereign' => '0'], true],
    'selected memberships' => [['memberships' => ['african_union', 'un']], true],
    'language' => [['language' => 'eng'], true],
    'zero population_max' => [['population_max' => '0'], true],
    'zero area_min' => [['area_min' => '0'], true],
    'zero area_max' => [['area_max' => '0'], true],
    'zero density_min' => [['density_min' => '0'], true],
    'zero density_max' => [['density_max' => '0'], true],
    'no filters' => [[], false],
    'region and subregion are not advanced' => [['region' => 'Africa', 'subregion' => 'Middle Africa'], false],
    'unknown membership is not a filter' => [['memberships' => ['test']], false],
    'invalid density is not a filter' => [['density_max' => 'abc'], false],
]);
