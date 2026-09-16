<?php

namespace App\Models;

use Database\Factories\CountryFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * A country synced from the REST Countries API (see CountriesSync).
 *
 * Matched during sync by restcountries_uuid, not cca3 — the ISO code isn't
 * guaranteed to be present/unique for every entry, while the API's own
 * uuid is guaranteed present and unique by construction.
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
    ];

    protected function casts(): array
    {
        return [
            'continents' => 'array',
        ];
    }

    /**
     * The capitals belonging to this country (can be more than one — see Capital).
     */
    public function capitals(): HasMany
    {
        return $this->hasMany(Capital::class);
    }
}
