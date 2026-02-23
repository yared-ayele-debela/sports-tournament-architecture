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
        Schema::create('matches', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('tournament_id');
            $table->unsignedBigInteger('venue_id');
            $table->unsignedBigInteger('home_team_id');
            $table->unsignedBigInteger('away_team_id');
            $table->unsignedBigInteger('referee_id');
            $table->datetime('match_date');
            $table->integer('round_number');
            $table->enum('status', ['scheduled', 'in_progress', 'completed', 'cancelled']);
            $table->integer('home_score')->nullable();
            $table->integer('away_score')->nullable();
            $table->integer('current_minute')->nullable();
            $table->timestamps();

            // Indexes
            $table->index('tournament_id');
            $table->index('status');
            $table->index('match_date');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('matches');
    }
};
