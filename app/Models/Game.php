<?php

namespace App\Models;

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
    ];

    protected $casts = [
        'player_count' => 'integer',
        'total_rounds' => 'integer',
        'current_round' => 'integer',
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

    public static function booted(): void
    {
        static::creating(function (Game $game) {
            // Auto-calculate total_rounds as player_count * 2
            $game->total_rounds = $game->player_count * 2;
        });
    }
}
