<?php

namespace App\Enums;

enum Membership: string
{
    case AfricanUnion = 'african_union';
    case ArabLeague = 'arab_league';
    case Asean = 'asean';
    case Brics = 'brics';
    case Commonwealth = 'commonwealth';
    case Eu = 'eu';
    case Eurozone = 'eurozone';
    case G20 = 'g20';
    case G7 = 'g7';
    case Nato = 'nato';
    case Oecd = 'oecd';
    case Opec = 'opec';
    case Schengen = 'schengen';
    case Un = 'un';

    public function label(): string
    {
        return match ($this) {
            self::AfricanUnion => 'African Union',
            self::ArabLeague => 'Arab League',
            self::Asean => 'ASEAN',
            self::Brics => 'BRICS',
            self::Commonwealth => 'Commonwealth of Nations',
            self::Eu => 'European Union',
            self::Eurozone => 'Eurozone',
            self::G20 => 'G20',
            self::G7 => 'G7',
            self::Nato => 'NATO',
            self::Oecd => 'OECD',
            self::Opec => 'OPEC',
            self::Schengen => 'Schengen Area',
            self::Un => 'United Nations',
        };
    }
}
