<?php

namespace App\Models;

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
    ];

    protected $casts = [
        'total_score' => 'integer',
        'double_used' => 'boolean',
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

    public function canUseDouble(): bool
    {
        return !$this->double_used;
    }

    public function isOnline(): bool
    {
        return $this->sessions()
            ->where('is_connected', true)
            ->where('last_seen_at', '>', now()->subMinutes(2))
            ->exists();
    }

    public function activeSession(): ?PlayerSession
    {
        return $this->sessions()
            ->where('is_connected', true)
            ->latest('last_seen_at')
            ->first();
    }
}
