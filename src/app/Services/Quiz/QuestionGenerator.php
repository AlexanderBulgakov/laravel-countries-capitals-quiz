<?php

namespace App\Services\Quiz;

use App\Enums\QuizMode;
use App\Models\Country;

class QuestionGenerator
{
    /**
     * whereHas('capitals') everywhere below — a country synced without a
     * capital row can't be rendered as a question or an option.
     */
    public function generate(QuizMode $mode, array $excludeCountryIds = []): GeneratedQuestion
    {
        $target = Country::query()
            ->whereHas('capitals')
            ->whereNotIn('id', $excludeCountryIds)
            ->inRandomOrder()
            ->first();

        $distractorIds = Country::query()
            ->whereHas('capitals')
            ->where('region', $target->region)
            ->whereNotIn('id', [...$excludeCountryIds, $target->id])
            ->inRandomOrder()
            ->limit(3)
            ->pluck('id');

        $missing = 3 - $distractorIds->count();

        // Not every region has ≥3 other countries with capital data — top up from
        // any region rather than shipping a 2- or 3-option question.
        if ($missing > 0) {
            $extraIds = Country::query()
                ->whereHas('capitals')
                ->whereNotIn('id', [...$excludeCountryIds, $target->id, ...$distractorIds])
                ->inRandomOrder()
                ->limit($missing)
                ->pluck('id');

            $distractorIds = $distractorIds->merge($extraIds);
        }

        $optionCountryIds = collect([$target->id])
            ->merge($distractorIds)
            ->shuffle()
            // Re-keys 0..3 after shuffle — without it, non-sequential int keys
            // would json_encode() as an object instead of an array.
            ->values()
            ->all();

        return new GeneratedQuestion(
            mode: $mode,
            targetCountryId: $target->id,
            optionCountryIds: $optionCountryIds,
        );
    }
}
