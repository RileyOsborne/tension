<?php

namespace Database\Factories;

use App\Models\Round;
use App\Models\Game;
use App\Models\Category;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Round>
 */
class RoundFactory extends Factory
{
    protected $model = Round::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'game_id' => Game::factory(),
            'category_id' => Category::factory(),
            'round_number' => 1,
            'status' => 'pending',
            'current_slide' => 0,
        ];
    }
}
