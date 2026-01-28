<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\Tournament;
use App\Models\Sport;

class TournamentSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $soccer = Sport::where('name', 'Soccer')->first();

        Tournament::firstOrCreate([
            'name' => 'National Championship 2024'
        ], [
            'sport_id' => $soccer->id,
            'location' => 'National Stadium, Capital City',
            'start_date' => now()->addDays(30)->format('Y-m-d'),
            'end_date' => now()->addDays(60)->format('Y-m-d'),
            'status' => 'planned',
            'created_at' => now(),
            'updated_at' => now()
        ]);

        Tournament::firstOrCreate([
            'name' => 'City Cup 2024'
        ], [
            'sport_id' => $soccer->id,
            'location' => 'City Sports Complex, Downtown District',
            'start_date' => now()->addDays(90)->format('Y-m-d'),
            'end_date' => now()->addDays(105)->format('Y-m-d'),
            'status' => 'planned',
            'created_at' => now(),
            'updated_at' => now()
        ]);

        $this->command->info('Tournaments seeded successfully!');
    }
}
