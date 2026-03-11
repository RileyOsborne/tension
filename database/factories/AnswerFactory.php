<?php

namespace Database\Factories;

use App\Models\Answer;
use App\Models\Category;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Answer>
 */
class AnswerFactory extends Factory
{
    protected $model = Answer::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $position = $this->faker->unique()->numberBetween(1, 15);
        $isFriction = $position > 10;
        
        return [
            'category_id' => Category::factory(),
            'text' => $this->faker->word,
            'stat' => $this->faker->numberBetween(1, 100),
            'position' => $position,
            'is_friction' => $isFriction,
            'points' => $isFriction ? -5 : $position,
        ];
    }
}
