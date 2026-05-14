<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        User::query()->updateOrCreate(['email' => 'owner@example.com'], [
            'name' => 'Владелец',
            'role' => User::ROLE_OWNER,
            'password' => 'password',
        ]);

        User::query()->updateOrCreate(['email' => 'finance@example.com'], [
            'name' => 'Финансовый менеджер',
            'role' => User::ROLE_FINANCE_MANAGER,
            'password' => 'password',
        ]);

        User::query()->updateOrCreate(['email' => 'viewer@example.com'], [
            'name' => 'Наблюдатель',
            'role' => User::ROLE_VIEWER,
            'password' => 'password',
        ]);
    }
}
