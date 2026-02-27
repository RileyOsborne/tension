<?php

namespace App\Http\Controllers;

use App\Events\PlayerJoinedGame;
use App\Events\PlayerLeftGame;
use App\Models\Game;
use App\Models\Player;
use App\Models\PlayerSession;
use App\Services\PlayerConnectionService;
use Illuminate\Http\Request;

class PlayerJoinController extends Controller
{
    public function __construct(
        private PlayerConnectionService $connectionService
    ) {}

    public function index()
    {
        return view('player.join');
    }

    public function findGame(Request $request)
    {
        $validated = $request->validate([
            'join_code' => 'required|string|size:6',
        ]);

        $game = Game::where('join_code', strtoupper($validated['join_code']))
            ->whereIn('status', ['draft', 'ready', 'playing'])
            ->first();

        if (!$game) {
            return back()->withErrors(['join_code' => 'Game not found or no longer accepting players.']);
        }

        return redirect()->route('player.select', ['game' => $game->join_code]);
    }

    public function selectPlayer(Game $game)
    {
        // Check if already has a valid session for this game
        $token = session('player_token');
        if ($token) {
            $existingSession = PlayerSession::where('session_token', $token)
                ->where('game_id', $game->id)
                ->first();

            if ($existingSession) {
                // Reactivate session
                $this->connectionService->heartbeat($token);
                return redirect()->route('player.play', ['game' => $game->id]);
            }
        }

        // Get players available to claim (GM-created, unclaimed)
        $availablePlayers = $this->connectionService->getAvailableToClaim($game);

        // Get disconnected players who can reconnect
        $disconnectedPlayers = $this->connectionService->getDisconnectedPlayers($game);

        // Players can always self-register
        $canRegister = true;

        return view('player.select', compact('game', 'availablePlayers', 'disconnectedPlayers', 'canRegister'));
    }

    public function claimPlayer(Request $request, Game $game)
    {
        $validated = $request->validate([
            'player_id' => 'required|exists:players,id',
        ]);

        $player = Player::findOrFail($validated['player_id']);

        if ($player->game_id !== $game->id) {
            abort(403, 'Player does not belong to this game.');
        }

        // Verify player is still available to claim
        if (!$player->isAvailableToClaim()) {
            return back()->withErrors(['player_id' => 'This player is no longer available.']);
        }

        // Create session
        $sessionToken = $this->connectionService->createSession($player, $game, $request->userAgent());

        // Store token in session
        session(['player_token' => $sessionToken, 'player_id' => $player->id]);

        // Broadcast player joined
        event(new PlayerJoinedGame($game, $player));

        return redirect()->route('player.play', ['game' => $game->id]);
    }

    public function reconnectPlayer(Request $request, Game $game)
    {
        $validated = $request->validate([
            'player_id' => 'required|exists:players,id',
        ]);

        $player = Player::findOrFail($validated['player_id']);

        if ($player->game_id !== $game->id) {
            abort(403, 'Player does not belong to this game.');
        }

        // Reconnect to existing player
        $sessionToken = $this->connectionService->reconnectPlayer(
            $player,
            $game,
            $request->userAgent()
        );

        session(['player_token' => $sessionToken, 'player_id' => $player->id]);
        event(new PlayerJoinedGame($game, $player));

        return redirect()->route('player.play', ['game' => $game->id]);
    }

    public function registerPlayer(Request $request, Game $game)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'color' => 'nullable|string|max:7',
        ]);

        $name = trim($validated['name']);

        // Check if a player with this name already exists in the game
        $existingPlayer = $this->connectionService->findPlayerByName($game, $name);

        if ($existingPlayer) {
            // Reconnect to existing player
            $sessionToken = $this->connectionService->reconnectPlayer(
                $existingPlayer,
                $game,
                $request->userAgent()
            );

            session(['player_token' => $sessionToken, 'player_id' => $existingPlayer->id]);
            event(new PlayerJoinedGame($game, $existingPlayer));

            return redirect()->route('player.play', ['game' => $game->id]);
        }

        // Create new player
        $position = $game->players()->max('position') + 1;

        // Use selected color or fall back to position-based color
        $colors = ['#EF4444', '#F97316', '#EAB308', '#22C55E', '#06B6D4', '#3B82F6', '#8B5CF6', '#EC4899'];
        $color = $validated['color'] ?? $colors[($position - 1) % count($colors)];

        $player = $game->players()->create([
            'name' => $name,
            'color' => $color,
            'position' => $position,
            'total_score' => 0,
            'double_used' => false,
        ]);

        // Update game player count
        $game->update(['player_count' => $game->players()->count()]);

        // Create session
        $sessionToken = $this->connectionService->createSession($player, $game, $request->userAgent());

        session(['player_token' => $sessionToken, 'player_id' => $player->id]);
        event(new PlayerJoinedGame($game, $player));

        return redirect()->route('player.play', ['game' => $game->id]);
    }

    /**
     * Heartbeat endpoint - called by player view polling.
     */
    public function heartbeat(Request $request)
    {
        $token = $request->input('token') ?? session('player_token');

        if ($token) {
            $this->connectionService->heartbeat($token);
        }

        return response()->json(['ok' => true]);
    }

    /**
     * Disconnect endpoint - called on page unload (best effort).
     * Broadcasts immediately for responsive UI, with heartbeat timeout as fallback.
     */
    public function disconnect(Request $request)
    {
        // Try to get token from request body or session
        $data = json_decode($request->getContent(), true);
        $token = $data['token'] ?? $request->input('token') ?? session('player_token');

        if ($token) {
            $session = PlayerSession::where('session_token', $token)->first();

            if ($session) {
                // Mark as disconnected
                $session->update(['last_seen_at' => now()->subMinutes(5)]);

                // Broadcast disconnect for immediate UI update
                $player = $session->player;
                $game = $session->game;

                if ($player && $game) {
                    event(new PlayerLeftGame($game, $player));
                }
            }
        }

        return response()->json(['ok' => true]);
    }
}
