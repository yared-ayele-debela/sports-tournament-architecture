<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\Permission;

class PermissionSeeder extends Seeder
{
    public function run(): void
    {
        $permissions = [
            ['name' => 'view_admin_dashboard'],
            ['name' => 'manage_users'],
            ['name' => 'manage_roles'],
            ['name' => 'manage_permissions'],
            ['name' => 'manage_sports'],
            ['name' => 'manage_tournaments'],
            ['name' => 'manage_venues'],
            ['name' => 'manage_teams'],
            ['name' => 'manage_players'],
            ['name' => 'manage_matches'],
            ['name' => 'manage_my_matches'],
            ['name' => 'record_events'],
            ['name' => 'submit_reports'],
            ['name' => 'view_public'],
            ['name' => 'view_referee_dashboard'],
            ['name' => 'view_coach_dashboard'],
            ['name' => 'manage_own_teams'],
            ['name' => 'manage_own_players'],
        ];

        foreach ($permissions as $permission) {
            Permission::firstOrCreate($permission);
        }
    }
}
