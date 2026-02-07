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
        Schema::table('matches', function (Blueprint $table) {
            $table->dateTime('started_at')->nullable()->after('current_minute');
            $table->dateTime('paused_at')->nullable()->after('started_at');
            $table->integer('total_paused_seconds')->default(0)->after('paused_at');
            $table->dateTime('last_minute_update')->nullable()->after('total_paused_seconds');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('matches', function (Blueprint $table) {
            $table->dropColumn(['started_at', 'paused_at', 'total_paused_seconds', 'last_minute_update']);
        });
    }
};
