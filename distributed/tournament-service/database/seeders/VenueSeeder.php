<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\Venue;

class VenueSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Run database seeds.
     */
    public function run(): void
    {
        $venues = [
            [
                'name' => 'National Stadium',
                'location' => 'Addis Ababa, Ethiopia',
                'capacity' => 15000,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'name' => 'City Sports Complex',
                'location' => 'Addis Ababa, Ethiopia',
                'capacity' => 8000,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'name' => 'Mekelle Arena',
                'location' => 'Mekelle, Ethiopia',
                'capacity' => 12000,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'name' => 'Bahir Dar Sports Center',
                'location' => 'Bahir Dar, Ethiopia',
                'capacity' => 6000,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'name' => 'Hawassa International Stadium',
                'location' => 'Hawassa, Ethiopia',
                'capacity' => 10000,
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ];

        foreach ($venues as $venue) {
            Venue::create($venue);
        }

        $this->command->info('Venues seeded successfully!');
    }
}
