<?php

namespace App\Models;

use App\Enums\WeatherCode;
use Database\Factories\CapitalWeatherFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * @property Carbon $observed_at
 */
class CapitalWeather extends Model
{
    /** @use HasFactory<CapitalWeatherFactory> */
    use HasFactory;

    // Sync runs hourly; normal max age ~75 min, one failed run ~135 min.
    public const STALE_AFTER_HOURS = 3;

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

    public function isStale(): bool
    {
        return $this->observed_at->lt(now()->subHours(self::STALE_AFTER_HOURS));
    }
}
