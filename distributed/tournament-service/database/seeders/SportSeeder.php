<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\Sport;

class SportSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Run database seeds.
     */
    public function run(): void
    {
        $sports = [
            [
                'name' => 'Soccer',
                'team_based' => true,
                'rules' => 'Standard FIFA rules',
                'description' => 'Association football, commonly known as soccer, is world\'s most popular sport played with a spherical ball between two teams of 11 players.',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'name' => 'Basketball',
                'team_based' => true,
                'rules' => 'Standard FIBA rules',
                'description' => 'Basketball is a team sport in which two teams, most commonly of five players each, opposing one another on a rectangular court.',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'name' => 'Tennis',
                'team_based' => false,
                'rules' => 'Standard ATP/WTA rules',
                'description' => 'Tennis is a racket sport that can be played individually against a single opponent (singles) or between two teams of two players each (doubles).',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'name' => 'Volleyball',
                'team_based' => true,
                'rules' => 'Standard FIVB rules',
                'description' => 'Volleyball is a team sport in which two teams of six players are separated by a net.',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'name' => 'Swimming',
                'team_based' => false,
                'rules' => 'Standard FINA rules',
                'description' => 'Swimming is an individual or team racing sport that requires the use of one\'s entire body to move through water.',
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
