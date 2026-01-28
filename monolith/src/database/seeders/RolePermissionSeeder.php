<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\Role;
use App\Models\Permission;

class RolePermissionSeeder extends Seeder
{
    public function run(): void
    {
        $rolePermissions = [
            'Administrator' => [
                'view_admin_dashboard',
                'manage_users',
                'manage_roles',
                'manage_permissions',
                'manage_sports',
                'manage_tournaments',
                'manage_venues',
                'manage_teams',
                'manage_players',
                'manage_matches',
                'record_events',
                'submit_reports',
                'view_public',
            ],
            'Coach' => [
                'view_coach_dashboard',
                'manage_own_teams',
                'manage_own_players',
                'view_public',
            ],
            'Referee' => [
                'view_referee_dashboard',
                'manage_matches',
                'record_events',
                'submit_reports',
                'view_public',
            ],
            'Spectator' => [
                'view_public',
            ],
        ];

        foreach ($rolePermissions as $roleName => $permissions) {
            $role = Role::where('name', $roleName)->first();
            
            if ($role) {
                foreach ($permissions as $permissionName) {
                    $permission = Permission::where('name', $permissionName)->first();
                    if ($permission && !$role->permissions()->where('permission_id', $permission->id)->exists()) {
                        $role->permissions()->attach($permission->id);
                    }
                }
            }
        }
    }
}
