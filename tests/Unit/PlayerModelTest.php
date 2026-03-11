<?php

namespace Tests\Unit;

use App\Models\Game;
use App\Models\Player;
use App\Models\Round;
use App\Models\Category;
use App\Models\PlayerAnswer;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PlayerModelTest extends TestCase
{
    use RefreshDatabase;

    public function test_can_use_double_respects_game_config()
    {
        $game = Game::factory()->create(['doubles_per_player' => 1]);
        $player = Player::factory()->create(['game_id' => $game->id]);
        $category = Category::factory()->create();
        $round1 = Round::factory()->create(['game_id' => $game->id, 'category_id' => $category->id, 'round_number' => 1]);
        $round2 = Round::factory()->create(['game_id' => $game->id, 'category_id' => $category->id, 'round_number' => 2]);

        $this->assertTrue($player->canUseDouble());
        $this->assertEquals(1, $player->doublesRemaining());

        // Use a double in round 1
        PlayerAnswer::factory()->create([
            'player_id' => $player->id,
            'round_id' => $round1->id,
            'was_doubled' => true
        ]);

        $this->assertFalse($player->canUseDouble());
        $this->assertEquals(0, $player->doublesRemaining());
    }

    public function test_doubles_remaining_with_multiple_allowed()
    {
        $game = Game::factory()->create(['doubles_per_player' => 2]);
        $player = Player::factory()->create(['game_id' => $game->id]);
        $category = Category::factory()->create();
        $round1 = Round::factory()->create(['game_id' => $game->id, 'category_id' => $category->id]);

        $this->assertEquals(2, $player->doublesRemaining());

        PlayerAnswer::factory()->create([
            'player_id' => $player->id,
            'round_id' => $round1->id,
            'was_doubled' => true
        ]);

        $this->assertTrue($player->canUseDouble());
        $this->assertEquals(1, $player->doublesRemaining());
    }

    public function test_is_removed_check()
    {
        $player = Player::factory()->create(['removed_at' => null]);
        $this->assertFalse($player->isRemoved());

        $player->update(['removed_at' => now()]);
        $this->assertTrue($player->isRemoved());
    }
}
