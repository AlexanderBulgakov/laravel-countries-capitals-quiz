<?php

namespace App\Enums;

enum WeatherCode: int
{
    case ClearSky = 0;
    case MainlyClear = 1;
    case PartlyCloudy = 2;
    case Overcast = 3;
    case Fog = 45;
    case DepositingRimeFog = 48;
    case LightDrizzle = 51;
    case ModerateDrizzle = 53;
    case DenseDrizzle = 55;
    case LightFreezingDrizzle = 56;
    case DenseFreezingDrizzle = 57;
    case SlightRain = 61;
    case ModerateRain = 63;
    case HeavyRain = 65;
    case LightFreezingRain = 66;
    case HeavyFreezingRain = 67;
    case SlightSnowFall = 71;
    case ModerateSnowFall = 73;
    case HeavySnowFall = 75;
    case SnowGrains = 77;
    case SlightRainShowers = 80;
    case ModerateRainShowers = 81;
    case ViolentRainShowers = 82;
    case SlightSnowShowers = 85;
    case HeavySnowShowers = 86;
    case Thunderstorm = 95;
    case ThunderstormWithSlightHail = 96;
    case ThunderstormWithHeavyHail = 99;

    public function label(): string
    {
        return match ($this) {
            self::ClearSky => 'Clear sky',
            self::MainlyClear => 'Mainly clear',
            self::PartlyCloudy => 'Partly cloudy',
            self::Overcast => 'Overcast',
            self::Fog => 'Fog',
            self::DepositingRimeFog => 'Depositing rime fog',
            self::LightDrizzle => 'Light drizzle',
            self::ModerateDrizzle => 'Moderate drizzle',
            self::DenseDrizzle => 'Dense drizzle',
            self::LightFreezingDrizzle => 'Light freezing drizzle',
            self::DenseFreezingDrizzle => 'Dense freezing drizzle',
            self::SlightRain => 'Slight rain',
            self::ModerateRain => 'Moderate rain',
            self::HeavyRain => 'Heavy rain',
            self::LightFreezingRain => 'Light freezing rain',
            self::HeavyFreezingRain => 'Heavy freezing rain',
            self::SlightSnowFall => 'Slight snow fall',
            self::ModerateSnowFall => 'Moderate snow fall',
            self::HeavySnowFall => 'Heavy snow fall',
            self::SnowGrains => 'Snow grains',
            self::SlightRainShowers => 'Slight rain showers',
            self::ModerateRainShowers => 'Moderate rain showers',
            self::ViolentRainShowers => 'Violent rain showers',
            self::SlightSnowShowers => 'Slight snow showers',
            self::HeavySnowShowers => 'Heavy snow showers',
            self::Thunderstorm => 'Thunderstorm',
            self::ThunderstormWithSlightHail => 'Thunderstorm with slight hail',
            self::ThunderstormWithHeavyHail => 'Thunderstorm with heavy hail',
        };
    }
}
