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
            $table->boolean('timer_running')->default(false)->after('join_mode');
            $table->timestamp('timer_started_at')->nullable()->after('timer_running');
            $table->boolean('show_rules')->default(true)->after('timer_started_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('games', function (Blueprint $table) {
            $table->dropColumn(['timer_running', 'timer_started_at', 'show_rules']);
        });
    }
};
