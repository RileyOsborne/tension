<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property string $id
 * @property string $player_id
 * @property string $round_id
 * @property string|null $answer_id
 * @property string $input_text
 * @property int $points_awarded
 * @property bool $was_doubled
 * @property-read \App\Models\Player $player
 * @property-read \App\Models\Round $round
 * @property-read \App\Models\Answer|null $answer
 */
class PlayerAnswer extends Model
{
    use HasFactory, HasUlids;

    protected $fillable = [
        'player_id',
        'round_id',
        'answer_id',
        'input_text',
        'points_awarded',
        'was_doubled',
    ];

    protected $casts = [
        'points_awarded' => 'integer',
        'was_doubled' => 'boolean',
    ];

    /**
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo<\App\Models\Player, $this>
     */
    public function player(): BelongsTo
    {
        return $this->belongsTo(Player::class);
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo<\App\Models\Round, $this>
     */
    public function round(): BelongsTo
    {
        return $this->belongsTo(Round::class);
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo<\App\Models\Answer, $this>
     */
    public function answer(): BelongsTo
    {
        return $this->belongsTo(Answer::class);
    }
}
