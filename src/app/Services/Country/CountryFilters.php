<?php

namespace App\Services\Country;

use App\Enums\Membership;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;

final readonly class CountryFilters
{
    /**
     * @param  list<Membership>  $memberships
     */
    public function __construct(
        public ?string $region,
        public ?string $subregion,
        public ?int $populationMin,
        public ?int $populationMax,
        public ?float $areaMin,
        public ?float $areaMax,
        public ?float $densityMin,
        public ?float $densityMax,
        public ?bool $sovereign,
        public ?string $language,
        public array $memberships
    ) {}

    public static function fromRequest(Request $request): self
    {
        return new self(
            region: self::string($request, 'region'),
            subregion: self::string($request, 'subregion'),
            populationMin: self::nonNegativeInt($request, 'population_min'),
            populationMax: self::nonNegativeInt($request, 'population_max'),
            areaMin: self::nonNegativeFloat($request, 'area_min'),
            areaMax: self::nonNegativeFloat($request, 'area_max'),
            densityMin: self::nonNegativeFloat($request, 'density_min'),
            densityMax: self::nonNegativeFloat($request, 'density_max'),
            sovereign: match ($request->query('sovereign')) {
                '1' => true,
                '0' => false,
                default => null,
            },
            language: self::languageCode($request),
            memberships: collect(Arr::wrap($request->query('memberships')))
                ->filter(fn ($value) => is_string($value))
                ->unique()
                ->map(fn (string $value) => Membership::tryFrom($value))
                ->filter()
                ->values()
                ->all(),
        );
    }

    public function hasAdvancedFilters(): bool
    {
        return $this->language !== null
            || $this->sovereign !== null
            || $this->populationMin !== null
            || $this->populationMax !== null
            || $this->areaMin !== null
            || $this->areaMax !== null
            || $this->densityMin !== null
            || $this->densityMax !== null
            || $this->memberships !== [];
    }

    private static function languageCode(Request $request): ?string
    {
        $code = self::string($request, 'language');

        return $code === null ? null : strtolower($code);
    }

    private static function string(Request $request, string $key): ?string
    {
        $value = $request->query($key);

        return is_string($value) && trim($value) !== '' ? trim($value) : null;
    }

    private static function nonNegativeInt(Request $request, string $key): ?int
    {
        $value = $request->query($key);

        if (! is_string($value)) {
            return null;
        }

        $int = filter_var($value, FILTER_VALIDATE_INT, ['options' => ['min_range' => 0]]);

        return $int === false ? null : $int;
    }

    private static function nonNegativeFloat(Request $request, string $key): ?float
    {
        $value = $request->query($key);

        if (! is_string($value)) {
            return null;
        }

        $float = filter_var($value, FILTER_VALIDATE_FLOAT, ['options' => ['min_range' => 0]]);

        return $float === false ? null : $float;
    }
}
