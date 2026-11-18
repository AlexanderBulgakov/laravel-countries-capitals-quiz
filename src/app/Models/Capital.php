<?php

namespace App\Models;

use Database\Factories\CapitalFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;

/**
 * A single capital city belonging to a country.
 *
 * Some countries have more than one capital (e.g. South Africa has
 * separate administrative, legislative and judicial capitals) — hence
 * this being its own table rather than a column on Country.
 *
 * @property int $id
 * @property string $name
 * @property string|null $lat
 * @property string|null $lng
 * @property-read CapitalWeather|null $weather
 */
class Capital extends Model
{
    /** @use HasFactory<CapitalFactory> */
    use HasFactory;

    protected $fillable = ['country_id', 'name', 'lat', 'lng'];

    /**
     * The country this capital belongs to.
     */
    public function country(): BelongsTo
    {
        return $this->belongsTo(Country::class);
    }

    /**
     * @return HasOne<CapitalWeather, $this>
     */
    public function weather(): HasOne
    {
        return $this->hasOne(CapitalWeather::class);
    }
}
