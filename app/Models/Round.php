<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * @property string $id
 * @property string $game_id
 * @property string $category_id
 * @property int $round_number
 * @property string $status
 * @property int $current_slide
 * @property-read \App\Models\Game $game
 * @property-read \App\Models\Category $category
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\PlayerAnswer> $playerAnswers
 */
class Round extends Model
{
    use HasFactory, HasUlids;

    protected $fillable = [
        'game_id',
        'category_id',
        'round_number',
        'status',
        'current_slide',
    ];

    protected $casts = [
        'round_number' => 'integer',
        'current_slide' => 'integer',
    ];

    /**
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo<\App\Models\Game, $this>
     */
    public function game(): BelongsTo
    {
        return $this->belongsTo(Game::class);
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo<\App\Models\Category, $this>
     */
    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\HasMany<\App\Models\PlayerAnswer, $this>
     */
    public function playerAnswers(): HasMany
    {
        return $this->hasMany(PlayerAnswer::class);
    }

    public function getCurrentAnswer(): ?Answer
    {
        if ($this->current_slide === 0) {
            return null; // Intro slide
        }

        return $this->category->answers()
            ->where('position', $this->current_slide)
            ->first();
    }

    public function getMaxSlide(): int
    {
        // Max slide is the highest position answer in the category
        return $this->category->answers()->max('position') ?? $this->game->top_answers_count;
    }

    public function isOnFriction(): bool
    {
        return $this->current_slide > $this->game->top_answers_count;
    }
}
