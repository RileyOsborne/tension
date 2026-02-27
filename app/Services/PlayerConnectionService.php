<?php

namespace App\Services;

use App\Events\PlayerJoinedGame;
use App\Models\Game;
use App\Models\Player;
use App\Models\PlayerSession;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;

class PlayerConnectionService
{
    /**
     * How many seconds before a player is considered disconnected.
     * Should be ~3x the heartbeat interval (5s) for reliability.
     */
    const TIMEOUT_SECONDS = 15;

    /**
     * Is this player currently connected?
     * - GM-created players (no sessions) are always "connected" (GM controls them)
     * - Self-registered players need a recent heartbeat
     */
    public function isConnected(Player $player): bool
    {
        if ($this->isGmCreated($player)) {
            return true;
        }

        return $player->sessions()
            ->where('last_seen_at', '>', now()->subSeconds(self::TIMEOUT_SECONDS))
            ->exists();
    }

    /**
     * Was this player created by the GM (vs self-registered)?
     */
    public function isGmCreated(Player $player): bool
    {
        return !$player->sessions()->exists();
    }

    /**
     * Is this player active in the game?
     *
     * Removed players are never active.
     * During lobby (draft/ready): only GM-created or connected players
     * During gameplay (playing/completed): all players who joined stay active
     *   - Disconnected players fall under GM control until they reconnect
     */
    public function isActive(Player $player): bool
    {
        // Removed players are never active
        if ($player->isRemoved()) {
            return false;
        }

        // GM-created players are always active
        if ($this->isGmCreated($player)) {
            return true;
        }

        // Self-registered players must have at least one session
        if (!$player->sessions()->exists()) {
            return false;
        }

        // During gameplay, all players who joined remain active (GM takes over if disconnected)
        $game = $player->game;
        if ($game && in_array($game->status, ['playing', 'completed'])) {
            return true;
        }

        // In lobby, only connected players are active
        return $this->isConnected($player);
    }

    /**
     * Is this player currently under GM control?
     * True if GM-created OR self-registered but disconnected during gameplay.
     */
    public function isGmControlled(Player $player): bool
    {
        if ($this->isGmCreated($player)) {
            return true;
        }

        // Self-registered but disconnected during gameplay = GM controlled
        $game = $player->game;
        if ($game && in_array($game->status, ['playing', 'completed'])) {
            return !$this->isConnected($player);
        }

        return false;
    }

    /**
     * Can this player be claimed on the join page?
     * Only GM-created players without an active controller.
     */
    public function isAvailableToClaim(Player $player): bool
    {
        if (!$this->isGmCreated($player)) {
            return false;
        }

        // Check if someone is actively controlling this player
        return !$player->sessions()
            ->where('last_seen_at', '>', now()->subSeconds(self::TIMEOUT_SECONDS))
            ->exists();
    }

    /**
     * Get all active players for a game (for display in lobbies).
     */
    public function getActivePlayers(Game $game): Collection
    {
        return $game->players
            ->filter(fn($p) => $this->isActive($p))
            ->sortBy('position')
            ->values();
    }

    /**
     * Get players available to claim on the join page.
     */
    public function getAvailableToClaim(Game $game): Collection
    {
        return $game->players
            ->filter(fn($p) => $this->isAvailableToClaim($p))
            ->sortBy('position')
            ->values();
    }

    /**
     * Get disconnected players who can reconnect (have sessions but timed out, not removed).
     */
    public function getDisconnectedPlayers(Game $game): Collection
    {
        return $game->players
            ->filter(fn($p) => !$p->isRemoved() && !$this->isGmCreated($p) && !$this->isConnected($p))
            ->sortBy('position')
            ->values();
    }

    /**
     * Get removed players for a game.
     */
    public function getRemovedPlayers(Game $game): Collection
    {
        return $game->players
            ->filter(fn($p) => $p->isRemoved())
            ->sortBy('position')
            ->values();
    }

    /**
     * Send a heartbeat to keep the player connected.
     * If player was disconnected and is now reconnecting, broadcast the event.
     */
    public function heartbeat(string $sessionToken): void
    {
        $session = PlayerSession::where('session_token', $sessionToken)->first();

        if (!$session) {
            return;
        }

        // Check if player was disconnected before this heartbeat
        $wasDisconnected = $session->last_seen_at < now()->subSeconds(self::TIMEOUT_SECONDS);

        // Update the heartbeat
        $session->update(['last_seen_at' => now()]);

        // If they were disconnected and are now reconnecting, broadcast the event
        if ($wasDisconnected) {
            $player = $session->player;
            $game = $session->game;

            if ($player && $game) {
                event(new PlayerJoinedGame($game, $player));
            }
        }
    }

    /**
     * Create a new session for a player (claiming or registering).
     */
    public function createSession(Player $player, Game $game, string $deviceName): string
    {
        $sessionToken = Str::random(64);

        PlayerSession::create([
            'player_id' => $player->id,
            'game_id' => $game->id,
            'session_token' => $sessionToken,
            'device_name' => $deviceName,
            'is_connected' => true,
            'last_seen_at' => now(),
        ]);

        return $sessionToken;
    }

    /**
     * Find an existing player by name in a game.
     */
    public function findPlayerByName(Game $game, string $name): ?Player
    {
        return $game->players()
            ->whereRaw('LOWER(name) = ?', [strtolower(trim($name))])
            ->first();
    }

    /**
     * Reconnect an existing player with a new session.
     */
    public function reconnectPlayer(Player $player, Game $game, string $deviceName): string
    {
        // Mark any existing sessions as stale by setting old last_seen_at
        $player->sessions()->update(['last_seen_at' => now()->subHour()]);

        // Create new session
        return $this->createSession($player, $game, $deviceName);
    }

    /**
     * Map players to array format for broadcasting.
     */
    public function playersToArray(Collection $players): array
    {
        return $players->map(fn($p) => [
            'id' => $p->id,
            'name' => $p->name,
            'color' => $p->color,
            'total_score' => $p->total_score,
            'double_used' => $p->double_used,
            'is_connected' => $this->isConnected($p),
            'is_gm_created' => $this->isGmCreated($p),
            'is_gm_controlled' => $this->isGmControlled($p),
        ])->values()->toArray();
    }
}
