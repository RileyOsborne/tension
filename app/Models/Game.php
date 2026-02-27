<?php

namespace App\Models;

use App\Enums\GameStatus;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Game extends Model
{
    use HasFactory, HasUlids;

    protected $fillable = [
        'name',
        'player_count',
        'total_rounds',
        'current_round',
        'status',
        'join_code',
        'thinking_time',
        'join_mode',
        'timer_running',
        'timer_started_at',
        'show_rules',
    ];

    protected $casts = [
        'player_count' => 'integer',
        'total_rounds' => 'integer',
        'current_round' => 'integer',
        'thinking_time' => 'integer',
        'timer_running' => 'boolean',
        'timer_started_at' => 'datetime',
        'show_rules' => 'boolean',
    ];

    public function players(): HasMany
    {
        return $this->hasMany(Player::class);
    }

    public function rounds(): HasMany
    {
        return $this->hasMany(Round::class)->orderBy('round_number');
    }

    public function currentRoundModel(): ?Round
    {
        return $this->rounds()->where('round_number', $this->current_round)->first();
    }

    public function playerSessions(): HasMany
    {
        return $this->hasMany(PlayerSession::class);
    }

    public static function generateJoinCode(): string
    {
        do {
            $code = strtoupper(substr(str_shuffle('ABCDEFGHJKLMNPQRSTUVWXYZ23456789'), 0, 6));
        } while (static::where('join_code', $code)->exists());

        return $code;
    }

    /**
     * Resolve route model binding - case-insensitive for join_code.
     */
    public function resolveRouteBinding($value, $field = null)
    {
        if ($field === 'join_code') {
            // Case-insensitive lookup for join_code
            return $this->where('join_code', strtoupper($value))->first();
        }

        return parent::resolveRouteBinding($value, $field);
    }

    public function getTurnOrderForRound(int $roundNumber): array
    {
        // Only include non-removed players in turn order
        $players = $this->players()
            ->whereNull('removed_at')
            ->orderBy('position')
            ->get();
        $count = $players->count();
        if ($count === 0) return [];

        $offset = ($roundNumber - 1) % $count;
        $ordered = $players->slice($offset)->merge($players->slice(0, $offset));

        return $ordered->values()->all();
    }

    public static function booted(): void
    {
        static::creating(function (Game $game) {
            $game->total_rounds = $game->player_count * 2;
            $game->join_code = static::generateJoinCode();
        });
    }
}
