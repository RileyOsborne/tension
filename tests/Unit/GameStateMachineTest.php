<?php

namespace Tests\Unit;

use App\Models\Game;
use App\Models\Player;
use App\Models\Round;
use App\Models\Category;
use App\Models\Answer;
use App\Models\PlayerAnswer;
use App\Services\GameStateMachine;
use App\Enums\GameStatus;
use App\Enums\RoundStatus;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class GameStateMachineTest extends TestCase
{
    use RefreshDatabase;

    public function test_start_game_transitions_status_and_sets_up_first_round()
    {
        $game = Game::factory()->create(['status' => GameStatus::Ready->value]);
        $round = Round::factory()->create(['game_id' => $game->id, 'round_number' => 1, 'status' => RoundStatus::Pending->value]);
        
        $stateMachine = new GameStateMachine($game);
        $stateMachine->startGame();
        
        $this->assertEquals(GameStatus::Playing->value, $game->refresh()->status);
        $this->assertEquals(RoundStatus::Intro->value, $round->refresh()->status);
        $this->assertTrue($game->show_rules);
    }

    public function test_dismiss_rules_updates_game_state()
    {
        $game = Game::factory()->create(['show_rules' => true]);
        $stateMachine = new GameStateMachine($game);
        
        $stateMachine->dismissRules();
        
        $this->assertFalse($game->refresh()->show_rules);
    }

    public function test_start_collecting_starts_timer_and_transitions_round()
    {
        $game = Game::factory()->create(['current_round' => 1, 'status' => GameStatus::Playing->value]);
        $round = Round::factory()->create(['game_id' => $game->id, 'round_number' => 1, 'status' => RoundStatus::Intro->value]);
        
        $stateMachine = new GameStateMachine($game);
        $stateMachine->startCollecting();
        
        $this->assertEquals(RoundStatus::Collecting->value, $round->refresh()->status);
        $this->assertTrue($game->refresh()->timer_running);
        $this->assertNotNull($game->timer_started_at);
    }

    public function test_submit_player_answer_calculates_points_and_advances_turn()
    {
        $game = Game::factory()->create([
            'current_round' => 1,
            'status' => GameStatus::Playing->value,
            'top_answers_count' => 10,
            'not_on_list_penalty' => -3
        ]);
        
        $player1 = Player::factory()->create(['game_id' => $game->id, 'position' => 1]);
        $player2 = Player::factory()->create(['game_id' => $game->id, 'position' => 2]);
        
        $category = Category::factory()->create();
        $answer = Answer::factory()->create(['category_id' => $category->id, 'text' => 'Apple', 'position' => 1]); // points will be 1
        
        $round = Round::factory()->create([
            'game_id' => $game->id, 
            'round_number' => 1, 
            'category_id' => $category->id,
            'status' => RoundStatus::Collecting->value
        ]);

        $stateMachine = new GameStateMachine($game);
        
        // Player 1 submits correct answer
        $stateMachine->submitPlayerAnswer($player1->id, 'Apple');
        
        $pa1 = PlayerAnswer::where('player_id', $player1->id)->first();
        $this->assertNotNull($pa1);
        $this->assertEquals($answer->id, $pa1->answer_id);
        $this->assertEquals(1, $pa1->points_awarded);
        $this->assertEquals(1, $player1->refresh()->total_score);

        // Turn should have advanced to player 2
        $turnInfo = $stateMachine->getCurrentTurnInfo();
        $this->assertEquals($player2->id, $turnInfo['currentPlayer']->id);
        $this->assertEquals('countup', $turnInfo['timerMode']);
    }

    public function test_reveal_next_increments_slide_and_transitions_to_friction()
    {
        $game = Game::factory()->create(['top_answers_count' => 1, 'status' => GameStatus::Playing->value]);
        $category = Category::factory()->create();
        Answer::factory()->create(['category_id' => $category->id, 'position' => 1]);
        Answer::factory()->create(['category_id' => $category->id, 'position' => 2]);
        
        $round = Round::factory()->create([
            'game_id' => $game->id,
            'category_id' => $category->id,
            'status' => RoundStatus::Revealing->value,
            'current_slide' => 0
        ]);

        $stateMachine = new GameStateMachine($game);
        
        $stateMachine->revealNext();
        $this->assertEquals(1, $round->refresh()->current_slide);
        $this->assertEquals(RoundStatus::Revealing->value, $round->status);

        $stateMachine->revealNext();
        $this->assertEquals(2, $round->refresh()->current_slide);
        $this->assertEquals(RoundStatus::Friction->value, $round->status);
    }

    public function test_next_round_transitions_to_intro_of_next_round()
    {
        $game = Game::factory()->create(['current_round' => 1, 'player_count' => 1, 'rounds_per_player' => 2, 'status' => GameStatus::Playing->value]);
        $round1 = Round::factory()->create(['game_id' => $game->id, 'round_number' => 1, 'status' => RoundStatus::Scoring->value]);
        $round2 = Round::factory()->create(['game_id' => $game->id, 'round_number' => 2, 'status' => RoundStatus::Pending->value]);
        
        $stateMachine = new GameStateMachine($game);
        
        $hasMore = $stateMachine->nextRound();
        
        $this->assertTrue($hasMore);
        $this->assertEquals(2, $game->refresh()->current_round);
        $this->assertEquals(RoundStatus::Complete->value, $round1->refresh()->status);
        $this->assertEquals(RoundStatus::Intro->value, $round2->refresh()->status);
    }

    public function test_next_round_completes_game_when_no_more_rounds()
    {
        $game = Game::factory()->create(['current_round' => 1, 'player_count' => 1, 'rounds_per_player' => 1, 'status' => GameStatus::Playing->value]);
        $round1 = Round::factory()->create(['game_id' => $game->id, 'round_number' => 1, 'status' => RoundStatus::Scoring->value]);
        
        $stateMachine = new GameStateMachine($game);
        
        $hasMore = $stateMachine->nextRound();
        
        $this->assertFalse($hasMore);
        $this->assertEquals(GameStatus::Completed->value, $game->refresh()->status);
    }
}
