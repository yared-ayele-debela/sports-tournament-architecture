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
                'name' => 'National Soccer Stadium',
                'location' => '123 Main Street, Sports City, SC 12345',
                'capacity' => 15000,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'name' => 'Community Sports Complex',
                'location' => '456 Park Avenue, Recreation Town, SC 67890',
                'capacity' => 8000,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'name' => 'University Athletic Field',
                'location' => '789 Campus Drive, University City, SC 11111',
                'capacity' => 12000,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'name' => 'Municipal Recreation Center',
                'location' => '321 City Hall Plaza, Metro Area, SC 22222',
                'capacity' => 5000,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'name' => 'Regional Sports Arena',
                'location' => '654 Arena Boulevard, Sports Hub, SC 33333',
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
