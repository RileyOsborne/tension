<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PlayerSession extends Model
{
    use HasUlids;

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

    public function player(): BelongsTo
    {
        return $this->belongsTo(Player::class);
    }

    public function game(): BelongsTo
    {
        return $this->belongsTo(Game::class);
    }
}
