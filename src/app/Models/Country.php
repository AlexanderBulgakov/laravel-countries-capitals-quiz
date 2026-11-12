<?php

namespace App\Models;

use Database\Factories\CountryFactory;
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
}
