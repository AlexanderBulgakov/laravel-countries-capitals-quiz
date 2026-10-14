<?php

namespace App\Enums;

use App\Models\Country;

enum QuizMode: string
{
    case CountryToCapital = 'country-capitals';
    case CapitalToCountry = 'capital-countries';

    public function label(): string
    {
        return match ($this) {
            self::CountryToCapital => 'Guess the capital',
            self::CapitalToCountry => 'Guess the country',
        };
    }

    public function promptLabel(Country $country): string
    {
        return match ($this) {
            self::CountryToCapital => $country->name_common,
            self::CapitalToCountry => $country->capitals->first()->name,
        };
    }

    public function optionLabel(Country $country): string
    {
        return match ($this) {
            // TODO: always picks the country's first capital (e.g. always Pretoria
            // for South Africa, never Cape Town/Bloemfontein) — a deliberate
            // simplification. Fix plan (pick one random capital id once at
            // generation time).
            self::CountryToCapital => $country->capitals->first()->name,
            self::CapitalToCountry => $country->name_common,
        };
    }
}
