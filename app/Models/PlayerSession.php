<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property string $id
 * @property string $player_id
 * @property string $game_id
 * @property string $session_token
 * @property string|null $device_name
 * @property bool $is_connected
 * @property \Illuminate\Support\Carbon|null $last_seen_at
 * @property-read \App\Models\Player $player
 * @property-read \App\Models\Game $game
 */
class PlayerSession extends Model
{
    use HasUlids, HasFactory;

    protected $fillable = [
        'player_id',
        'game_id',
        'session_token',
        'device_name',
        'is_connected',
        'last_seen_at',
    ];

    protected $casts = [
        'is_connected' => 'boolean',
        'last_seen_at' => 'datetime',
    ];

    /**
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo<\App\Models\Player, $this>
     */
    public function player(): BelongsTo
    {
        return $this->belongsTo(Player::class);
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo<\App\Models\Game, $this>
     */
    public function game(): BelongsTo
    {
        return $this->belongsTo(Game::class);
    }
}
