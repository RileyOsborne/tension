<?php

namespace Tests\Unit;

use App\Models\Game;
use App\Models\Player;
use App\Models\PlayerSession;
use App\Services\PlayerConnectionService;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PlayerConnectionServiceTest extends TestCase
{
    use RefreshDatabase;

    private PlayerConnectionService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = new PlayerConnectionService();
    }

    public function test_is_gm_created_detects_lack_of_sessions()
    {
        $player = Player::factory()->create();
        $this->assertTrue($this->service->isGmCreated($player));

        PlayerSession::factory()->create(['player_id' => $player->id]);
        $this->assertFalse($this->service->isGmCreated($player->refresh()));
    }

    public function test_is_connected_checks_heartbeat_timeout()
    {
        $player = Player::factory()->create();
        
        // GM created players are always connected
        $this->assertTrue($this->service->isConnected($player));

        // Create a session for the player (making them self-registered/claimed)
        $session = PlayerSession::factory()->create([
            'player_id' => $player->id,
            'last_seen_at' => now()
        ]);
        
        $this->assertTrue($this->service->isConnected($player->refresh()));

        // Move time forward past timeout
        Carbon::setTestNow(now()->addSeconds(PlayerConnectionService::TIMEOUT_SECONDS + 1));
        
        $this->assertFalse($this->service->isConnected($player->refresh()));
        
        Carbon::setTestNow(); // Reset
    }

    public function test_is_active_during_lobby()
    {
        $game = Game::factory()->create(['status' => 'ready']);
        $player = Player::factory()->create(['game_id' => $game->id]);
        
        // GM created is active
        $this->assertTrue($this->service->isActive($player));

        // Self-registered and connected is active
        $session = PlayerSession::factory()->create([
            'player_id' => $player->id,
            'game_id' => $game->id,
            'last_seen_at' => now()
        ]);
        $this->assertTrue($this->service->isActive($player->refresh()));

        // Self-registered and disconnected is NOT active in lobby
        Carbon::setTestNow(now()->addSeconds(PlayerConnectionService::TIMEOUT_SECONDS + 1));
        $this->assertFalse($this->service->isActive($player->refresh()));
        
        Carbon::setTestNow();
    }

    public function test_is_active_during_gameplay()
    {
        $game = Game::factory()->create(['status' => 'playing']);
        $player = Player::factory()->create(['game_id' => $game->id]);
        
        // Create session
        PlayerSession::factory()->create([
            'player_id' => $player->id,
            'game_id' => $game->id,
            'last_seen_at' => now()
        ]);

        // Disconnected players remain active during gameplay (GM takes over)
        Carbon::setTestNow(now()->addSeconds(PlayerConnectionService::TIMEOUT_SECONDS + 1));
        $this->assertTrue($this->service->isActive($player->refresh()));
        
        Carbon::setTestNow();
    }

    public function test_is_gm_controlled()
    {
        $game = Game::factory()->create(['status' => 'playing']);
        $player = Player::factory()->create(['game_id' => $game->id]);
        
        // GM created is controlled
        $this->assertTrue($this->service->isGmControlled($player));

        // Self-registered and connected is NOT controlled
        PlayerSession::factory()->create([
            'player_id' => $player->id,
            'game_id' => $game->id,
            'last_seen_at' => now()
        ]);
        $this->assertFalse($this->service->isGmControlled($player->refresh()));

        // Self-registered and disconnected during gameplay IS controlled
        Carbon::setTestNow(now()->addSeconds(PlayerConnectionService::TIMEOUT_SECONDS + 1));
        $this->assertTrue($this->service->isGmControlled($player->refresh()));
        
        Carbon::setTestNow();
    }

    public function test_is_available_to_claim()
    {
        $player = Player::factory()->create();
        
        // GM created without any session is available
        $this->assertTrue($this->service->isAvailableToClaim($player));

        // Once a session is created (even if expired), it is no longer "GM created" 
        // by the current definition (which means "never had a session")
        // and thus no longer available to claim as a NEW player.
        PlayerSession::factory()->create([
            'player_id' => $player->id,
            'last_seen_at' => now()
        ]);
        $this->assertFalse($this->service->isAvailableToClaim($player->refresh()));

        // Even after timeout, it's still not available to "claim" because it has a session history.
        // It would instead show up in "disconnected players" for reconnection.
        Carbon::setTestNow(now()->addSeconds(PlayerConnectionService::TIMEOUT_SECONDS + 1));
        $this->assertFalse($this->service->isAvailableToClaim($player->refresh()));
        
        Carbon::setTestNow();
    }

    public function test_heartbeat_updates_last_seen_at()
    {
        $session = PlayerSession::factory()->create(['last_seen_at' => now()->subMinutes(1)]);
        
        $this->service->heartbeat($session->session_token);
        
        $this->assertTrue($session->refresh()->last_seen_at->isAfter(now()->subSeconds(5)));
    }
}
