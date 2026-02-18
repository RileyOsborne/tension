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
        'is_tension',
        'points',
    ];

    protected $casts = [
        'position' => 'integer',
        'is_tension' => 'boolean',
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

    public static function booted(): void
    {
        static::creating(function (Answer $answer) {
            // Auto-calculate is_tension and points based on position
            $answer->is_tension = $answer->position > 10;
            $answer->points = $answer->is_tension ? -5 : $answer->position;
        });

        static::updating(function (Answer $answer) {
            // Recalculate on update
            $answer->is_tension = $answer->position > 10;
            $answer->points = $answer->is_tension ? -5 : $answer->position;
        });
    }
}
