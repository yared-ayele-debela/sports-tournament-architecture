<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\Role;
use App\Models\Permission;

class RolePermissionSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Get all permissions
        $permissions = Permission::all();
        
        // Get roles
        $administratorRole = Role::find(1); // Administrator
        $coachRole = Role::find(2); // Coach
        $refereeRole = Role::find(3); // Referee

        // Administrator: All permissions
        if ($administratorRole) {
            $administratorRole->permissions()->attach($permissions->pluck('id'));
        }

        // Coach: manage_players (own team)
        if ($coachRole) {
            $coachPermissions = $permissions->whereIn('name', ['manage_players']);
            $coachRole->permissions()->attach($coachPermissions->pluck('id'));
        }

        // Referee: record_events, submit_reports
        if ($refereeRole) {
            $refereePermissions = $permissions->whereIn('name', ['record_events', 'submit_reports']);
            $refereeRole->permissions()->attach($refereePermissions->pluck('id'));
        }

        $this->command->info('Role permissions seeded successfully!');
    }
}
