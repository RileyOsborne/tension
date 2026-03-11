<?php

namespace Tests\Unit;

use App\Models\Game;
use App\Models\Player;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class GameModelTest extends TestCase
{
    use RefreshDatabase;

    public function test_join_code_is_generated_on_creation()
    {
        $game = Game::create([
            'name' => 'Test Game',
        ]);

        $this->assertNotNull($game->join_code);
        $this->assertEquals(6, strlen($game->join_code));
    }

    public function test_total_rounds_calculated_from_player_count()
    {
        // Default rounds_per_player is 2 (from migration/factory)
        $game = Game::create([
            'name' => 'Test Game',
            'player_count' => 4,
            'rounds_per_player' => 2
        ]);

        $this->assertEquals(8, $game->total_rounds);
    }

    public function test_recalculate_from_players_updates_counts()
    {
        $game = Game::factory()->create(['rounds_per_player' => 3]);
        
        Player::factory()->count(3)->create(['game_id' => $game->id]);
        
        $game->recalculateFromPlayers();
        
        $this->assertEquals(3, $game->player_count);
        $this->assertEquals(9, $game->total_rounds);
    }

    public function test_recalculate_ignores_removed_players()
    {
        $game = Game::factory()->create(['rounds_per_player' => 2]);
        
        Player::factory()->count(2)->create(['game_id' => $game->id]);
        Player::factory()->create([
            'game_id' => $game->id,
            'removed_at' => now()
        ]);
        
        $game->recalculateFromPlayers();
        
        $this->assertEquals(2, $game->player_count);
        $this->assertEquals(4, $game->total_rounds);
    }

    public function test_calculate_points_respects_config()
    {
        $game = Game::factory()->create([
            'top_answers_count' => 10,
            'friction_penalty' => -5
        ]);

        $this->assertEquals(1, $game->calculatePoints(1));
        $this->assertEquals(10, $game->calculatePoints(10));
        $this->assertEquals(-5, $game->calculatePoints(11));
    }
}
