<?php

namespace App\Models;

use App\Enums\Membership;
use App\Services\Country\CountryFilters;
use Database\Factories\CountryFactory;
use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * A country synced from the REST Countries API (see CountriesSync).
 *
 * Matched during sync by restcountries_uuid, not cca3 — the ISO code isn't
 * guaranteed to be present/unique for every entry, while the API's own
 * uuid is guaranteed present and unique by construction.
 *
 * @property int $id
 * @property string $name_common
 * @property-read Collection<int, Capital> $capitals
 * @property list<string> $memberships
 * @property ?string $subregion
 * @property ?string $area_km2
 * @property ?string $density
 * @property ?bool $sovereign
 */
class Country extends Model
{
    /** @use HasFactory<CountryFactory> */
    use HasFactory;

    protected $fillable = [
        'restcountries_uuid',
        'cca3',
        'name_common',
        'name_official',
        'region',
        'continents',
        'descriptions_short',
        'flag_url',
        'population',
        'subregion',
        'area_km2',
        'sovereign',
        'memberships',
    ];

    protected function casts(): array
    {
        return [
            'continents' => 'array',
            'memberships' => 'array',
            'sovereign' => 'boolean',
            'area_km2' => 'decimal:2',
            'density' => 'decimal:2',
            'population' => 'integer',
        ];
    }

    /**
     * The capitals belonging to this country (can be more than one — see Capital).
     */
    public function capitals(): HasMany
    {
        return $this->hasMany(Capital::class);
    }

    /**
     * @return BelongsToMany<Language, $this>
     */
    public function languages(): BelongsToMany
    {
        return $this->belongsToMany(Language::class);
    }

    /**
     * @param  Builder<self>  $query
     */
    #[Scope]
    protected function filter(Builder $query, CountryFilters $filters): void
    {
        $query
            ->when(
                $filters->region !== null,
                fn ($q) => $q->where('region', $filters->region)
            )
            ->when(
                $filters->subregion !== null,
                fn ($q) => $q->where('subregion', $filters->subregion)
            )
            ->when(
                $filters->sovereign !== null,
                fn ($q) => $q->where('sovereign', $filters->sovereign)
            )
            ->when(
                $filters->language !== null,
                fn ($q) => $q->whereRelation('languages', 'iso639_3', $filters->language)
            )
            ->when(
                $filters->memberships !== [],
                fn ($q) => $q->whereJsonContains('memberships', $filters->memberships)
            );

        $this->whereRange($query, 'population', $filters->populationMin, $filters->populationMax);
        $this->whereRange($query, 'area_km2', $filters->areaMin, $filters->areaMax);
        $this->whereRange($query, 'density', $filters->densityMin, $filters->densityMax);
    }

    /**
     * @param  Builder<self>  $query
     */
    private function whereRange(Builder $query, string $column, int|float|null $min, int|float|null $max): void
    {
        $query
            ->when($min !== null, fn ($q) => $q->where($column, '>=', $min))
            ->when($max !== null, fn ($q) => $q->where($column, '<=', $max));
    }

    /** @return list<Membership> */
    public function knownMemberships(): array
    {
        return collect($this->memberships)
            ->map(fn (string $key) => Membership::tryFrom($key))
            ->filter()
            ->values()
            ->all();
    }
}
