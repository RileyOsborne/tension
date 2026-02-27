<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * Adds configurable game parameters with sensible defaults.
     * These allow game variants without code changes.
     */
    public function up(): void
    {
        Schema::table('games', function (Blueprint $table) {
            // Scoring configuration
            $table->integer('top_answers_count')->default(10);      // Positions 1-N are safe zone
            $table->integer('tension_penalty')->default(-5);         // Points for tension zone (position > top_answers_count)
            $table->integer('not_on_list_penalty')->default(-3);     // Points when answer not found

            // Game structure
            $table->integer('rounds_per_player')->default(2);        // Total rounds = player_count * this

            // Double mechanics
            $table->integer('double_multiplier')->default(2);        // Multiplier when double is used
            $table->integer('doubles_per_player')->default(1);       // How many doubles each player gets

            // Category limits
            $table->integer('max_answers_per_category')->default(15); // Maximum answers a category can have
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('games', function (Blueprint $table) {
            $table->dropColumn([
                'top_answers_count',
                'tension_penalty',
                'not_on_list_penalty',
                'rounds_per_player',
                'double_multiplier',
                'doubles_per_player',
                'max_answers_per_category',
            ]);
        });
    }
};
