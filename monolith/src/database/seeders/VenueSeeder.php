<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\Venue;

class VenueSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $venues = [
            [
                'name' => 'National Stadium',
                'location' => '123 Stadium Road, Capital City',
                'capacity' => 45000,
                'created_at' => now(),
                'updated_at' => now()
            ],
            [
                'name' => 'City Sports Complex',
                'location' => '456 Sports Avenue, Downtown District',
                'capacity' => 25000,
                'created_at' => now(),
                'updated_at' => now()
            ],
            [
                'name' => 'University Field',
                'location' => '789 Campus Drive, University District',
                'capacity' => 15000,
                'created_at' => now(),
                'updated_at' => now()
            ],
            [
                'name' => 'Municipal Ground',
                'location' => '321 Park Lane, Suburban Area',
                'capacity' => 12000,
                'created_at' => now(),
                'updated_at' => now()
            ],
            [
                'name' => 'Elite Training Center',
                'location' => '654 Academy Boulevard, Sports District',
                'capacity' => 8000,
                'created_at' => now(),
                'updated_at' => now()
            ],
            [
                'name' => 'Riverside Arena',
                'location' => '987 Waterfront Drive, Riverside District',
                'capacity' => 18000,
                'created_at' => now(),
                'updated_at' => now()
            ]
        ];

        foreach ($venues as $venue) {
            Venue::firstOrCreate(['name' => $venue['name']], $venue);
        }

        $this->command->info('Venues seeded successfully!');
    }
}
