<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Rename tension_penalty to friction_penalty in games table
        Schema::table('games', function (Blueprint $table) {
            $table->renameColumn('tension_penalty', 'friction_penalty');
        });

        // Rename is_tension to is_friction in answers table
        Schema::table('answers', function (Blueprint $table) {
            $table->renameColumn('is_tension', 'is_friction');
        });

        // Update rounds status enum value from 'tension' to 'friction'
        DB::statement("UPDATE rounds SET status = 'friction' WHERE status = 'tension'");
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Rename friction_penalty back to tension_penalty
        Schema::table('games', function (Blueprint $table) {
            $table->renameColumn('friction_penalty', 'tension_penalty');
        });

        // Rename is_friction back to is_tension
        Schema::table('answers', function (Blueprint $table) {
            $table->renameColumn('is_friction', 'is_tension');
        });

        // Update rounds status enum value from 'friction' to 'tension'
        DB::statement("UPDATE rounds SET status = 'tension' WHERE status = 'friction'");
    }
};
