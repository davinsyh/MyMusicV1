<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // Seed a regular user
        User::factory()->create([
            'name' => 'Test User',
            'username' => 'user',
            'email' => 'user@example.com',
            'password' => bcrypt('password'),
            'role' => 'user',
        ]);

        // Seed an admin user
        User::factory()->create([
            'name' => 'Admin User',
            'username' => 'admin',
            'email' => 'admin@example.com',
            'password' => bcrypt('password'),
            'role' => 'admin',
        ]);
    }
}
