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
            'name' => 'Владелец',
            'email' => 'owner@example.com',
            'role' => User::ROLE_OWNER,
        ]);

        User::factory()->create([
            'name' => 'Финансовый менеджер',
            'email' => 'finance@example.com',
            'role' => User::ROLE_FINANCE_MANAGER,
        ]);

        User::factory()->create([
            'name' => 'Наблюдатель',
            'email' => 'viewer@example.com',
            'role' => User::ROLE_VIEWER,
        ]);
    }
}
