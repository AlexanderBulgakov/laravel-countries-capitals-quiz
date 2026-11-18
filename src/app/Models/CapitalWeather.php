<?php

namespace App\Models;

use App\Enums\WeatherCode;
use Database\Factories\CapitalWeatherFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CapitalWeather extends Model
{
    /** @use HasFactory<CapitalWeatherFactory> */
    use HasFactory;

    protected $table = 'capital_weather';

    protected $fillable = [
        'capital_id',
        'temperature_c',
        'wind_speed_kmh',
        'weather_code',
        'is_day',
        'observed_at',
    ];

    protected function casts(): array
    {
        return [
            'temperature_c' => 'decimal:1',
            'wind_speed_kmh' => 'decimal:1',
            'weather_code' => WeatherCode::class,
            'is_day' => 'boolean',
            'observed_at' => 'datetime',
        ];
    }

    /**
     * @return BelongsTo<Capital, $this>
     */
    public function capital(): BelongsTo
    {
        return $this->belongsTo(Capital::class);
    }
}
