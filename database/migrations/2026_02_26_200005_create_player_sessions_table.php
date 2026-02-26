<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('player_sessions', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->foreignUlid('player_id')->constrained()->cascadeOnDelete();
            $table->foreignUlid('game_id')->constrained()->cascadeOnDelete();
            $table->string('session_token', 64)->unique();
            $table->string('device_name')->nullable();
            $table->boolean('is_connected')->default(false);
            $table->timestamp('last_seen_at')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('player_sessions');
    }
};
