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
        Schema::table('games', function (Blueprint $table) {
            $table->string('join_code', 6)->unique()->nullable()->after('status');
            $table->unsignedInteger('thinking_time')->default(30)->after('join_code');
            $table->enum('join_mode', ['claim', 'register'])->default('claim')->after('thinking_time');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('games', function (Blueprint $table) {
            $table->dropColumn(['join_code', 'thinking_time', 'join_mode']);
        });
    }
};
