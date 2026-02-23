<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\Role;

class RoleSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $roles = [
            [
                'id' => 1,
                'name' => 'Administrator',
                'description' => 'Full system administrator with all permissions',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'id' => 2,
                'name' => 'Coach',
                'description' => 'Team coach with player management permissions',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'id' => 3,
                'name' => 'Referee',
                'description' => 'Match referee with event recording and reporting permissions',
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ];

        foreach ($roles as $role) {
            Role::firstOrCreate(
                ['id' => $role['id']],
                $role
            );
        }

        $this->command->info('Roles seeded successfully!');
    }
}
