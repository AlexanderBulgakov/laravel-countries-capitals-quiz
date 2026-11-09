<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property list<int> $option_country_ids
 * @property list<string> $lifelines
 */
class QuizAnswer extends Model
{
    protected $fillable = [
        'question_number',
        'target_country_id',
        'chosen_country_id',
        'option_country_ids',
        'lifelines',
    ];

    protected function casts(): array
    {
        return [
            'question_number' => 'integer',
            'target_country_id' => 'integer',
            'chosen_country_id' => 'integer',
            'option_country_ids' => 'array',
            'lifelines' => 'array',
        ];
    }

    public function isCorrect(): bool
    {
        return $this->chosen_country_id === $this->target_country_id;
    }

    /**
     * @return BelongsTo<QuizAttempt, $this>
     */
    public function quizAttempt(): BelongsTo
    {
        return $this->belongsTo(QuizAttempt::class);
    }

    /**
     * @return BelongsTo<Country, $this>
     */
    public function targetCountry(): BelongsTo
    {
        return $this->belongsTo(Country::class, 'target_country_id');
    }

    /**
     * @return BelongsTo<Country, $this>
     */
    public function chosenCountry(): BelongsTo
    {
        return $this->belongsTo(Country::class, 'chosen_country_id');
    }
}
