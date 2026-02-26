<?php

namespace Database\Seeders;

use App\Models\Topic;
use Illuminate\Database\Seeder;

class TopicSeeder extends Seeder
{
    /**
     * Seed standard topics for the Tension game.
     */
    public function run(): void
    {
        $topics = [
            'Geography',
            'History',
            'Science',
            'Sports',
            'Entertainment',
            'Music',
            'Movies',
            'Television',
            'Food & Drink',
            'Animals',
            'Technology',
            'Business',
            'Pop Culture',
            'Literature',
            'Art',
        ];

        foreach ($topics as $name) {
            Topic::firstOrCreate(['name' => $name]);
        }
    }
}
