<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use App\Models\User;
use App\Models\Role;

class UserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Create roles first
        $adminRole = Role::firstOrCreate(['name' => 'admin']);
        $coachRole = Role::firstOrCreate(['name' => 'coach']);
        $refereeRole = Role::firstOrCreate(['name' => 'referee']);

        // Create admin users
        $admin1 = User::firstOrCreate([
            'email' => 'admin@tournament.com'
        ], [
            'name' => 'John Administrator',
            'password' => Hash::make('password'),
            'created_at' => now(),
            'updated_at' => now()
        ]);
        $admin1->roles()->sync([$adminRole->id]);

        $admin2 = User::firstOrCreate([
            'email' => 'sarah@tournament.com'
        ], [
            'name' => 'Sarah Manager',
            'password' => Hash::make('password'),
            'created_at' => now(),
            'updated_at' => now()
        ]);
        $admin2->roles()->sync([$adminRole->id]);

        // Create coach users
        $coach1 = User::firstOrCreate([
            'email' => 'coach1@tournament.com'
        ], [
            'name' => 'Michael Thompson',
            'password' => Hash::make('password'),
            'created_at' => now(),
            'updated_at' => now()
        ]);
        $coach1->roles()->sync([$coachRole->id]);

        $coach2 = User::firstOrCreate([
            'email' => 'coach2@tournament.com'
        ], [
            'name' => 'David Martinez',
            'password' => Hash::make('password'),
            'created_at' => now(),
            'updated_at' => now()
        ]);
        $coach2->roles()->sync([$coachRole->id]);

        $coach3 = User::firstOrCreate([
            'email' => 'coach3@tournament.com'
        ], [
            'name' => 'Robert Chen',
            'password' => Hash::make('password'),
            'created_at' => now(),
            'updated_at' => now()
        ]);
        $coach3->roles()->sync([$coachRole->id]);

        $coach4 = User::firstOrCreate([
            'email' => 'coach4@tournament.com'
        ], [
            'name' => 'James Wilson',
            'password' => Hash::make('password'),
            'created_at' => now(),
            'updated_at' => now()
        ]);
        $coach4->roles()->sync([$coachRole->id]);

        // Create referee users
        $referee1 = User::firstOrCreate([
            'email' => 'referee1@tournament.com'
        ], [
            'name' => 'Thomas Anderson',
            'password' => Hash::make('password'),
            'created_at' => now(),
            'updated_at' => now()
        ]);
        $referee1->roles()->sync([$refereeRole->id]);

        $referee2 = User::firstOrCreate([
            'email' => 'referee2@tournament.com'
        ], [
            'name' => 'Maria Rodriguez',
            'password' => Hash::make('password'),
            'created_at' => now(),
            'updated_at' => now()
        ]);
        $referee2->roles()->sync([$refereeRole->id]);

        $referee3 = User::firstOrCreate([
            'email' => 'referee3@tournament.com'
        ], [
            'name' => 'Peter Johnson',
            'password' => Hash::make('password'),
            'created_at' => now(),
            'updated_at' => now()
        ]);
        $referee3->roles()->sync([$refereeRole->id]);

        $this->command->info('Users seeded successfully!');
        $this->command->info('Admin credentials: admin@tournament.com / password');
        $this->command->info('Coach credentials: coach1@tournament.com / password');
        $this->command->info('Referee credentials: referee1@tournament.com / password');
    }
}
