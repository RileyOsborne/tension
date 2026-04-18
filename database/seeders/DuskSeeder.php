<?php

namespace Database\Seeders;

use App\Models\Game;
use App\Models\Player;
use App\Models\Round;
use App\Models\Category;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class DuskSeeder extends Seeder
{
    public function run(): void
    {
        $joinCode = 'DSGN26';
        
        // Use the connection if specified, otherwise default
        $connection = $this->container->bound('db.connection') ? DB::connection()->getName() : config('database.default');
        
        echo "Seeding Dusk game to connection: $connection\n";

        // 1. Create Game
        $game = Game::create([
            'name' => 'Design Gallery Game',
            'status' => 'ready',
            'player_count' => 3,
            'total_rounds' => 6,
            'current_round' => 1,
            'top_answers_count' => 10,
            'friction_penalty' => -5,
            'rounds_per_player' => 2,
            'doubles_per_player' => 1,
        ]);
        $game->join_code = $joinCode;
        $game->save();

        // 2. Create Players
        $players = [
            ['name' => 'RILEY', 'color' => '#3b82f6', 'position' => 1],
            ['name' => 'JORDAN', 'color' => '#ef4444', 'position' => 2],
            ['name' => 'ALEX', 'color' => '#10b981', 'position' => 3],
        ];

        foreach ($players as $p) {
            Player::create(array_merge($p, ['game_id' => $game->id]));
        }

        // 3. Create Rounds
        $categories = Category::all();
        if ($categories->isEmpty()) {
            throw new \Exception("No categories found. Run StarterPackSeeder first.");
        }

        for ($i = 1; $i <= 6; $i++) {
            Round::create([
                'game_id' => $game->id,
                'category_id' => $categories->get($i % $categories->count())->id,
                'round_number' => $i,
                'status' => 'pending',
            ]);
        }
        
        // Set first round to Intro as expected by test
        $game->rounds()->first()->update(['status' => 'intro']);
    }
}
