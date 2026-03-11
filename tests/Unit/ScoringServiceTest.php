<?php

namespace Tests\Unit;

use App\Models\Game;
use App\Models\Player;
use App\Models\Round;
use App\Models\PlayerAnswer;
use App\Services\ScoringService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ScoringServiceTest extends TestCase
{
    use RefreshDatabase;

    private ScoringService $scorer;

    protected function setUp(): void
    {
        parent::setUp();
        $this->scorer = new ScoringService();
    }

    public function test_calculate_points_returns_base_points_when_no_double()
    {
        $game = Game::factory()->create();
        $player = Player::factory()->create(['game_id' => $game->id]);
        
        $points = $this->scorer->calculatePoints(10, $game, false, $player);
        
        $this->assertEquals(10, $points);
    }

    public function test_calculate_points_applies_not_on_list_penalty()
    {
        $game = Game::factory()->create(['not_on_list_penalty' => -3]);
        $player = Player::factory()->create(['game_id' => $game->id]);
        
        $points = $this->scorer->calculatePoints(null, $game, false, $player);
        
        $this->assertEquals(-3, $points);
    }

    public function test_calculate_points_applies_double_multiplier()
    {
        $game = Game::factory()->create(['double_multiplier' => 2]);
        $player = Player::factory()->create(['game_id' => $game->id]);
        
        $points = $this->scorer->calculatePoints(10, $game, true, $player);
        
        $this->assertEquals(20, $points);
    }

    public function test_calculate_points_applies_double_multiplier_to_penalty()
    {
        $game = Game::factory()->create(['not_on_list_penalty' => -3, 'double_multiplier' => 2]);
        $player = Player::factory()->create(['game_id' => $game->id]);
        
        $points = $this->scorer->calculatePoints(null, $game, true, $player);
        
        $this->assertEquals(-6, $points);
    }

    public function test_calculate_points_does_not_double_if_player_already_used_all_doubles()
    {
        $game = Game::factory()->create(['double_multiplier' => 2, 'doubles_per_player' => 1]);
        $player = Player::factory()->create(['game_id' => $game->id]);
        $round = Round::factory()->create(['game_id' => $game->id]);
        
        // Use up the double
        PlayerAnswer::factory()->create([
            'player_id' => $player->id,
            'round_id' => $round->id,
            'was_doubled' => true,
        ]);

        $points = $this->scorer->calculatePoints(10, $game, true, $player);
        
        $this->assertEquals(10, $points);
    }

    public function test_recalculate_scores_updates_player_total_score()
    {
        $game = Game::factory()->create();
        $player = Player::factory()->create(['game_id' => $game->id]);
        $round1 = Round::factory()->create(['game_id' => $game->id, 'round_number' => 1]);
        $round2 = Round::factory()->create(['game_id' => $game->id, 'round_number' => 2]);

        PlayerAnswer::factory()->create([
            'player_id' => $player->id,
            'round_id' => $round1->id,
            'points_awarded' => 10,
        ]);

        PlayerAnswer::factory()->create([
            'player_id' => $player->id,
            'round_id' => $round2->id,
            'points_awarded' => -5,
        ]);

        $this->scorer->recalculateScores($game);
        
        $this->assertEquals(5, $player->refresh()->total_score);
    }
}
