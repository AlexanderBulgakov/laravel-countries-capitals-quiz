<?php

use App\Enums\Difficulty;
use App\Enums\QuizMode;
use App\Models\Capital;
use App\Models\Country;
use App\Services\Quiz\QuestionGenerator;
use Illuminate\Database\Eloquent\Factories\Sequence;

/**
 * Draws three targets per difficulty, excluding each pick — on 9 countries
 * this exhausts every tier, so the result is deterministic.
 */
function pickTargetsPerDifficulty(): array
{
    $generator = app(QuestionGenerator::class);
    $excludedIds = [];
    $picked = [];

    foreach (Difficulty::cases() as $difficulty) {
        foreach (range(1, 3) as $_) {
            $id = $generator->generate(QuizMode::CountryToCapital, $excludedIds, $difficulty)->targetCountryId;
            $excludedIds[] = $id;
            $picked[$difficulty->name][] = $id;
        }
    }

    return $picked;
}

it('splits targets into population thirds, most populous first', function () {
    $countries = Country::factory()
        ->count(9)
        ->has(Capital::factory())
        ->sequence(fn (Sequence $sequence) => ['population' => ($sequence->index + 1) * 1_000_000])
        ->create();

    $idsByPopulation = $countries->sortByDesc('population')->pluck('id');
    $easyIds = $idsByPopulation->slice(0, 3)->values()->all();
    $mediumIds = $idsByPopulation->slice(3, 3)->values()->all();
    $hardIds = $idsByPopulation->slice(6, 3)->values()->all();

    $picked = pickTargetsPerDifficulty();

    expect($picked['Easy'])->toEqualCanonicalizing($easyIds)
        ->and($picked['Medium'])->toEqualCanonicalizing($mediumIds)
        ->and($picked['Hard'])->toEqualCanonicalizing($hardIds);
});

it('ranks countries without population into the hard tier', function () {
    Country::factory()
        ->count(8)
        ->has(Capital::factory())
        ->sequence(fn (Sequence $sequence) => ['population' => ($sequence->index + 1) * 1_000_000])
        ->create();

    $unknownPopulation = Country::factory()
        ->has(Capital::factory())
        ->create(['population' => null]);

    $picked = pickTargetsPerDifficulty();

    expect($picked['Easy'])->not->toContain($unknownPopulation->id)
        ->and($picked['Medium'])->not->toContain($unknownPopulation->id)
        ->and($picked['Hard'])->toContain($unknownPopulation->id);
});

it('falls back to any country when the requested tier is exhausted', function () {
    $countries = Country::factory()
        ->count(9)
        ->has(Capital::factory())
        ->sequence(fn (Sequence $sequence) => ['population' => ($sequence->index + 1) * 1_000_000])
        ->create();

    $idsByPopulation = $countries->sortByDesc('population')->pluck('id');
    $hardIds = $idsByPopulation->slice(6, 3)->values()->all();
    $otherIds = $idsByPopulation->slice(0, 6)->values()->all();

    $id = app(QuestionGenerator::class)->generate(QuizMode::CountryToCapital, $hardIds, Difficulty::Hard)->targetCountryId;

    expect($id)->toBeIn($otherIds);
});
