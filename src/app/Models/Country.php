<?php

namespace App\Models;

use Database\Factories\CountryFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

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

    public function capitals(): HasMany
    {
        return $this->hasMany(Capital::class);
    }
}
