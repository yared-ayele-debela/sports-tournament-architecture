<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\Permission;

class PermissionSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $permissions = [
            [
                'name' => 'manage_sports',
                'description' => 'Manage sports and sport categories',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'name' => 'manage_tournaments',
                'description' => 'Manage tournaments and tournament settings',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'name' => 'manage_venues',
                'description' => 'Manage venues and venue information',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'name' => 'manage_teams',
                'description' => 'Manage teams and team information',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'name' => 'manage_players',
                'description' => 'Manage players and player information',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'name' => 'manage_matches',
                'description' => 'Manage matches and match scheduling',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'name' => 'record_events',
                'description' => 'Record match events and scores',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'name' => 'submit_reports',
                'description' => 'Submit match reports and summaries',
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ];

        foreach ($permissions as $permission) {
            Permission::firstOrCreate(
                ['name' => $permission['name']],
                $permission
            );
        }

        $this->command->info('Permissions seeded successfully!');
    }
}
