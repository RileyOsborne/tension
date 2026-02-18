<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('player_answers', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->foreignUlid('round_id')->constrained()->cascadeOnDelete();
            $table->foreignUlid('player_id')->constrained()->cascadeOnDelete();
            $table->foreignUlid('answer_id')->nullable()->constrained()->cascadeOnDelete();
            $table->integer('points_awarded');
            $table->boolean('was_doubled')->default(false);
            $table->timestamps();

            // Each answer can only be assigned once per round
            $table->unique(['round_id', 'answer_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('player_answers');
    }
};
