<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\Sport;

class SportSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        Sport::firstOrCreate([
            'name' => 'Soccer'
        ], [
            'team_based' => true,
            'description' => 'Association football, more commonly known as football or soccer, is a team sport played with a spherical ball between two teams of 11 players.',
            'created_at' => now(),
            'updated_at' => now()
        ]);

        Sport::firstOrCreate([
            'name' => 'Basketball'
        ], [
            'team_based' => true,
            'description' => 'Basketball is a team sport in which two teams, most commonly of five players each, opposing one another on a rectangular court.',
            'created_at' => now(),
            'updated_at' => now()
        ]);

        Sport::firstOrCreate([
            'name' => 'Tennis'
        ], [
            'team_based' => false,
            'description' => 'Tennis is a racket sport that can be played individually against a single opponent or between two teams of two players each.',
            'created_at' => now(),
            'updated_at' => now()
        ]);

        $this->command->info('Sports seeded successfully!');
    }
}
