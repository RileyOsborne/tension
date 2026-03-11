<?php

namespace Tests\Feature;

use App\Models\Game;
use App\Models\Player;
use App\Models\Round;
use App\Models\Category;
use App\Models\Answer;
use App\Enums\GameStatus;
use App\Enums\RoundStatus;
use App\Events\PlayerAnswerSubmitted;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use Livewire\Volt\Volt;
use Tests\TestCase;

class PlayerViewTest extends TestCase
{
    use RefreshDatabase;

    private Game $game;
    private Player $player;

    protected function setUp(): void
    {
        parent::setUp();

        $this->game = Game::factory()->create([
            'status' => 'ready',
            'join_code' => 'ABCDEF',
            'player_count' => 0
        ]);
    }

    public function test_player_can_view_join_page()
    {
        $response = $this->get(route('player.join'));
        $response->assertStatus(200);
    }

    public function test_player_can_search_for_game()
    {
        $this->game->update(['status' => 'ready']);
        $response = $this->withoutMiddleware()->post(route('player.join.find'), ['join_code' => $this->game->join_code]);
        $response->assertRedirect(route('player.select', $this->game->join_code));
    }

    public function test_player_can_submit_answer_during_collecting_phase()
    {
        Event::fake();

        $this->game->update([
            'status' => 'playing',
            'player_count' => 1
        ]);
        
        $this->player = Player::factory()->create(['game_id' => $this->game->id]);
        
        $category = Category::factory()->create();
        for ($i = 1; $i <= 10; $i++) {
            Answer::factory()->create(['category_id' => $category->id, 'position' => $i]);
        }

        $round = Round::factory()->create([
            'game_id' => $this->game->id,
            'round_number' => 1,
            'category_id' => $category->id,
            'status' => RoundStatus::Collecting->value
        ]);

        // Simulating the player's session
        session(['player_token' => 'test-token', 'player_id' => $this->player->id]);

        // Mock the PlayerConnectionService to avoid heartbeat validation issues in tests
        $this->mock(\App\Services\PlayerConnectionService::class, function ($mock) {
            $mock->shouldReceive('heartbeat')->andReturn(null);
            $mock->shouldReceive('isActive')->andReturn(true);
            $mock->shouldReceive('getActivePlayers')->andReturn(collect());
        });

        Volt::test('pages.player.play', ['game' => $this->game])
            ->set('answerText', 'Player Answer')
            ->call('submitAnswer');

        $this->assertDatabaseHas('player_answers', [
            'player_id' => $this->player->id,
            'round_id' => $round->id,
            'input_text' => 'Player Answer'
        ]);

        Event::assertDispatched(PlayerAnswerSubmitted::class, function ($event) {
            return $event->game->id === $this->game->id &&
                   $event->player->id === $this->player->id &&
                   $event->answerText === 'Player Answer';
        });
    }
}
