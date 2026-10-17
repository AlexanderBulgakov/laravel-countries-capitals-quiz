<?php

namespace App\Models;

use App\Enums\QuizMode;
use App\Enums\QuizOutcome;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class QuizAttempt extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'mode',
        'outcome',
        'score',
        'stopped_at_question',
        'money',
    ];

    protected function casts(): array
    {
        return [
            'mode' => QuizMode::class,
            'outcome' => QuizOutcome::class,
            'score' => 'integer',
            'stopped_at_question' => 'integer',
            'money' => 'integer',
        ];
    }

    public function answers(): HasMany
    {
        return $this->hasMany(QuizAnswer::class)->orderBy('question_number');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
