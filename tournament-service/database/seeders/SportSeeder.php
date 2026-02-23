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
            ]
        ];

        foreach ($sports as $sport) {
            Sport::firstOrCreate(['name' => $sport['name']], $sport);
        }

        $this->command->info('Sports seeded successfully!');
    }
}
