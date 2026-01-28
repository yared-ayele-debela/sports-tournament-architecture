<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\Role;

class RoleSeeder extends Seeder
{
    public function run(): void
    {
        $roles = [
            ['name' => 'Administrator'],
            ['name' => 'Coach'],
            ['name' => 'Referee'],
            ['name' => 'Spectator'],
        ];

        foreach ($roles as $role) {
            Role::firstOrCreate($role, [
                'created_at' => now(),
                'updated_at' => now()
            ]);
        }
    }
}
