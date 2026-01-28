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
        Schema::create('tournament_settings', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('tournament_id')->unique();
            $table->integer('match_duration'); // minutes
            $table->integer('win_rest_time')->nullable(); // minutes
            $table->time('daily_start_time');
            $table->time('daily_end_time');
            $table->dateTime('created_at');
            $table->dateTime('updated_at');
            
            $table->foreign('tournament_id')->references('id')->on('tournaments')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('tournament_settings');
    }
};
