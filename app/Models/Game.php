<?php

namespace App\Models;

use App\Enums\GameStatus;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * @property string $id
 * @property string $name
 * @property int $player_count
 * @property int $total_rounds
 * @property int $current_round
 * @property string $status
 * @property string $join_code
 * @property int $thinking_time
 * @property string $join_mode
 * @property bool $timer_running
 * @property \Illuminate\Support\Carbon|null $timer_started_at
 * @property bool $show_rules
 * @property int $top_answers_count
 * @property int $friction_penalty
 * @property int $not_on_list_penalty
 * @property int $rounds_per_player
 * @property int $double_multiplier
 * @property int $doubles_per_player
 * @property int $max_answers_per_category
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\Player> $players
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\Round> $rounds
 */
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
        // Game configuration
        'top_answers_count',
        'friction_penalty',
        'not_on_list_penalty',
        'rounds_per_player',
        'double_multiplier',
        'doubles_per_player',
        'max_answers_per_category',
    ];

    protected $casts = [
        'player_count' => 'integer',
        'total_rounds' => 'integer',
        'current_round' => 'integer',
        'thinking_time' => 'integer',
        'timer_running' => 'boolean',
        'timer_started_at' => 'datetime',
        'show_rules' => 'boolean',
        // Game configuration
        'top_answers_count' => 'integer',
        'friction_penalty' => 'integer',
        'not_on_list_penalty' => 'integer',
        'rounds_per_player' => 'integer',
        'double_multiplier' => 'integer',
        'doubles_per_player' => 'integer',
        'max_answers_per_category' => 'integer',
    ];

    /**
     * @return \Illuminate\Database\Eloquent\Relations\HasMany<\App\Models\Player, $this>
     */
    public function players(): HasMany
    {
        return $this->hasMany(Player::class);
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\HasMany<\App\Models\Round, $this>
     */
    public function rounds(): HasMany
    {
        return $this->hasMany(Round::class)->orderBy('round_number');
    }

    public function currentRoundModel(): ?Round
    {
        /** @var Round|null $round */
        $round = $this->rounds()->where('round_number', $this->current_round)->first();
        return $round;
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\HasMany<\App\Models\PlayerSession, $this>
     */
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
            // Initialize player_count to 0 if not set (players join dynamically)
            $game->player_count = $game->player_count ?? 0;
            // Total rounds will be calculated dynamically as players join
            $game->total_rounds = $game->player_count * ($game->rounds_per_player ?? 2);
            $game->join_code = static::generateJoinCode();
        });
    }

    /**
     * Recalculate player_count and total_rounds based on active players.
     * Call this after players join, leave, or are removed.
     */
    public function recalculateFromPlayers(): void
    {
        $activeCount = $this->players()
            ->whereNull('removed_at')
            ->count();

        $this->update([
            'player_count' => $activeCount,
            'total_rounds' => $activeCount * ($this->rounds_per_player ?? 2),
        ]);
    }

    /**
     * Calculate points for a given position based on this game's config.
     */
    public function calculatePoints(int $position): int
    {
        if ($position > $this->top_answers_count) {
            return $this->friction_penalty;
        }
        return $position;
    }

    /**
     * Check if a position is in the friction zone for this game.
     */
    public function isPositionFriction(int $position): bool
    {
        return $position > $this->top_answers_count;
    }
}
