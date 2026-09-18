<?php

namespace App\Enums;

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
}
