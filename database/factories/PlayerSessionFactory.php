<?php

namespace Database\Factories;

use App\Models\Game;
use App\Models\Player;
use App\Models\PlayerSession;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\PlayerSession>
 */
class PlayerSessionFactory extends Factory
{
    protected $model = PlayerSession::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'player_id' => Player::factory(),
            'game_id' => Game::factory(),
            'session_token' => Str::random(64),
            'device_name' => 'Test Device',
            'is_connected' => true,
            'last_seen_at' => now(),
        ];
    }
}
