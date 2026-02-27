<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Answer extends Model
{
    use HasFactory, HasUlids;

    protected $fillable = [
        'category_id',
        'text',
        'stat',
        'position',
        'is_friction',
        'points',
    ];

    protected $casts = [
        'position' => 'integer',
        'is_friction' => 'boolean',
        'points' => 'integer',
    ];

    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }

    public function playerAnswers(): HasMany
    {
        return $this->hasMany(PlayerAnswer::class);
    }

    /**
     * Get the display text for GM input, hiding geographic identifiers.
     * E.g., "Great Wall of China, China" becomes "Great Wall of China"
     * E.g., "Mount Rushmore, South Dakota, USA" becomes "Mount Rushmore"
     */
    public function getDisplayTextAttribute(): string
    {
        // Split by comma and only show the first part (the main answer)
        $parts = explode(',', $this->text);
        return trim($parts[0]);
    }

    public static function booted(): void
    {
        static::creating(function (Answer $answer) {
            // Auto-calculate is_friction and points based on position
            $answer->is_friction = $answer->position > 10;
            $answer->points = $answer->is_friction ? -5 : $answer->position;
        });

        static::updating(function (Answer $answer) {
            // Recalculate on update
            $answer->is_friction = $answer->position > 10;
            $answer->points = $answer->is_friction ? -5 : $answer->position;
        });
    }
}
