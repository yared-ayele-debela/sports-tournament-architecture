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
        Schema::create('tournaments', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('sport_id');
            $table->string('name');
            $table->string('location')->nullable();
            $table->date('start_date');
            $table->date('end_date');
            $table->string('status'); // planned, ongoing, completed, cancelled
            $table->dateTime('created_at');
            $table->dateTime('updated_at');
            
            $table->foreign('sport_id')->references('id')->on('sports')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('tournaments');
    }
};
