<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('answers', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->foreignUlid('category_id')->constrained()->cascadeOnDelete();
            $table->string('text');
            $table->integer('position'); // 1-10 for top 10, 11-15 for tension
            $table->boolean('is_tension')->default(false);
            $table->integer('points'); // position for 1-10, -5 for tension
            $table->timestamps();

            $table->unique(['category_id', 'position']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('answers');
    }
};
