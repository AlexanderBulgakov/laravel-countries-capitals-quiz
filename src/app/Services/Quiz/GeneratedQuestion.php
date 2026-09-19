<?php

namespace App\Services\Quiz;

use App\Enums\QuizMode;

/**
 * A single generated quiz question — which countries participate and in
 * what order, decided once and then replayed identically from the session
 * (see GuestQuizSession::current()) instead of re-rolling on every request.
 *
 * Carries only ids, never display data (name/flag/capital name) — hydrating
 * those is a rendering concern, kept separate so this stays trivially
 * testable and mode-agnostic.
 */
final readonly class GeneratedQuestion
{
    public function __construct(
        public QuizMode $mode,
        public int $targetCountryId,
        /**
         * Always Country ids, in both modes — even for capital_to_country,
         * an option is "the country whose capital this is", not a Capital id.
         * Includes $targetCountryId, already shuffled.
         */
        public array $optionCountryIds,
    ) {}
}
