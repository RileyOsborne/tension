<?php

namespace Database\Factories;

use App\Models\Player;
use App\Models\Game;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Player>
 */
class PlayerFactory extends Factory
{
    protected $model = Player::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'game_id' => Game::factory(),
            'name' => $this->faker->firstName,
            'color' => $this->faker->safeHexColor,
            'total_score' => 0,
            'position' => 0,
        ];
    }
}
