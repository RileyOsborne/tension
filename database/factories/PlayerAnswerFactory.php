<?php

namespace Database\Factories;

use App\Models\PlayerAnswer;
use App\Models\Player;
use App\Models\Round;
use App\Models\Answer;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\PlayerAnswer>
 */
class PlayerAnswerFactory extends Factory
{
    protected $model = PlayerAnswer::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'player_id' => Player::factory(),
            'round_id' => Round::factory(),
            'answer_id' => Answer::factory(),
            'input_text' => $this->faker->word,
            'points_awarded' => 10,
            'was_doubled' => false,
            'answer_order' => 1,
        ];
    }
}
