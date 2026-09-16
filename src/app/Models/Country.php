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
