<?php

namespace Database\Seeders;

use App\Models\User;
// use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        User::factory()->create([
            'name' => 'Owner',
            'email' => 'owner@example.com',
            'role' => User::ROLE_OWNER,
        ]);

        User::factory()->create([
            'name' => 'Finance Manager',
            'email' => 'finance@example.com',
            'role' => User::ROLE_FINANCE_MANAGER,
        ]);

        User::factory()->create([
            'name' => 'Viewer',
            'email' => 'viewer@example.com',
            'role' => User::ROLE_VIEWER,
        ]);
    }
}
