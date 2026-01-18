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
        $sports = [
            [
                'name' => 'Soccer',
                'team_based' => true,
                'rules' => 'Standard FIFA rules with 11 players per team, 90-minute matches, and offside enforcement.',
                'description' => 'Association football, the world\'s most popular sport played with a spherical ball between two teams of 11 players.',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'name' => 'Basketball',
                'team_based' => true,
                'rules' => 'Standard NBA rules with 5 players per team, 12-minute quarters, and 3-point line.',
                'description' => 'A team sport played with a basketball between two teams of five players in a rectangular court.',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'name' => 'Tennis',
                'team_based' => false,
                'rules' => 'Standard ATP/WTA rules with best-of-3 sets, tiebreak at 6-6, and 2-point advantage.',
                'description' => 'An individual racket sport played between two players or two pairs using strung rackets to strike a hollow rubber ball.',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'name' => 'Volleyball',
                'team_based' => true,
                'rules' => 'Standard FIVB rules with 6 players per team, best-of-5 sets, and 25-point sets.',
                'description' => 'A team sport played with a volleyball between two teams of six players separated by a net.',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'name' => 'Swimming',
                'team_based' => false,
                'rules' => 'Standard FINA rules with freestyle, backstroke, breaststroke, and butterfly strokes.',
                'description' => 'An individual water sport involving various competitive swimming styles and distances.',
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ];

        foreach ($sports as $sport) {
            Sport::create($sport);
        }

        $this->command->info('Sports seeded successfully!');
    }
}
