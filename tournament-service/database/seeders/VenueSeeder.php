<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use App\Models\Venue;

class VenueSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Run database seeds.
     */
    public function run(): void
    {
        // Clear existing venues to avoid duplicates
        Venue::query()->delete();

        $venues = [
            [
                'name' => 'Stadio Olimpico',
                'location' => 'Rome, Italy',
                'capacity' => 70634,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'name' => 'San Siro (Stadio Giuseppe Meazza)',
                'location' => 'Milan, Italy',
                'capacity' => 80018,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'name' => 'Allianz Stadium',
                'location' => 'Turin, Italy',
                'capacity' => 41507,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'name' => 'Stadio Diego Armando Maradona',
                'location' => 'Naples, Italy',
                'capacity' => 54726,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'name' => 'Stadio Artemio Franchi',
                'location' => 'Florence, Italy',
                'capacity' => 43147,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'name' => 'Stadio Renato Dall\'Ara',
                'location' => 'Bologna, Italy',
                'capacity' => 38401,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'name' => 'Stadio Luigi Ferraris',
                'location' => 'Genoa, Italy',
                'capacity' => 36599,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'name' => 'Stadio Ennio Tardini',
                'location' => 'Parma, Italy',
                'capacity' => 27906,
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
