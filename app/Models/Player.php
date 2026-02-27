<?php

namespace App\Models;

use App\Services\PlayerConnectionService;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Player extends Model
{
    use HasFactory, HasUlids;

    protected $fillable = [
        'game_id',
        'name',
        'color',
        'position',
        'total_score',
        'double_used',
        'removed_at',
    ];

    protected $casts = [
        'total_score' => 'integer',
        'double_used' => 'boolean',
        'removed_at' => 'datetime',
    ];

    public function game(): BelongsTo
    {
        return $this->belongsTo(Game::class);
    }

    public function playerAnswers(): HasMany
    {
        return $this->hasMany(PlayerAnswer::class);
    }

    public function sessions(): HasMany
    {
        return $this->hasMany(PlayerSession::class);
    }

    /**
     * Check if player can use their double.
     * Counts doubles already used against the game's doubles_per_player limit.
     */
    public function canUseDouble(): bool
    {
        $doublesUsed = $this->playerAnswers()
            ->whereHas('round', fn($q) => $q->where('game_id', $this->game_id))
            ->where('was_doubled', true)
            ->count();

        return $doublesUsed < $this->game->doubles_per_player;
    }

    /**
     * Get the number of doubles remaining for this player.
     */
    public function doublesRemaining(): int
    {
        $doublesUsed = $this->playerAnswers()
            ->whereHas('round', fn($q) => $q->where('game_id', $this->game_id))
            ->where('was_doubled', true)
            ->count();

        return max(0, $this->game->doubles_per_player - $doublesUsed);
    }

    /**
     * All connection-related methods delegate to the service for single source of truth.
     */
    public function isGmCreated(): bool
    {
        return app(PlayerConnectionService::class)->isGmCreated($this);
    }

    public function isConnected(): bool
    {
        return app(PlayerConnectionService::class)->isConnected($this);
    }

    public function isActive(): bool
    {
        return app(PlayerConnectionService::class)->isActive($this);
    }

    public function isAvailableToClaim(): bool
    {
        return app(PlayerConnectionService::class)->isAvailableToClaim($this);
    }

    /**
     * Is this player currently under GM control?
     * True if GM-created OR self-registered but disconnected during gameplay.
     */
    public function isGmControlled(): bool
    {
        return app(PlayerConnectionService::class)->isGmControlled($this);
    }

    /**
     * Has this player been removed from the game by the GM?
     */
    public function isRemoved(): bool
    {
        return $this->removed_at !== null;
    }
}
