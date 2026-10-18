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
        $email = env('BIBLIOTECA_ADMIN_EMAIL');
        $password = env('BIBLIOTECA_ADMIN_PASSWORD');

        if ($email && $password) {
            User::query()->firstOrCreate(
                ['email' => $email],
                ['name' => env('BIBLIOTECA_ADMIN_NOMBRE', 'Voluntariado'), 'password' => $password]
            );
        }
    }
}
