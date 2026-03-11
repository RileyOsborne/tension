<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property string $id
 * @property string $category_id
 * @property string $text
 * @property string|null $stat
 * @property int $position
 * @property-read \App\Models\Category $category
 * @property-read string $display_text
 * @property-read int $points
 * @property-read bool $is_friction
 */
class Answer extends Model
{
    use HasFactory, HasUlids;

    protected $fillable = [
        'category_id',
        'text',
        'stat',
        'position',
    ];

    protected $casts = [
        'position' => 'integer',
    ];

    /**
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo<\App\Models\Category, $this>
     */
    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }

    /**
     * Get the display text for this answer.
     */
    public function getDisplayTextAttribute(): string
    {
        return $this->text;
    }

    /**
     * Get the points for this answer based on its position.
     */
    public function getPointsAttribute(): int
    {
        return $this->position;
    }

    /**
     * Determine if this answer is a friction answer.
     */
    public function getIsFrictionAttribute(): bool
    {
        // Default to anything above 10 being friction, 
        // but this usually depends on game config.
        return $this->position > 10;
    }
}
