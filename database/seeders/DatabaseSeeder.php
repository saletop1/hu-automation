<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\DB;

class DatabaseSeeder extends Seeder
{
    public function run()
    {
        // Hapus data existing terlebih dahulu (opsional)
        // User::truncate();

        // Create default admin user - dengan check existing
        User::firstOrCreate(
            ['email' => 'admin@sap-hu.com'],
            [
                'name' => 'Administrator',
                'password' => Hash::make('password123'),
                'email_verified_at' => now(),
            ]
        );

        // Create operator user - dengan check existing
        User::firstOrCreate(
            ['email' => 'operator@sap-hu.com'],
            [
                'name' => 'Operator HU',
                'password' => Hash::make('operator123'),
                'email_verified_at' => now(),
            ]
        );

        $this->command->info('Users seeded successfully!');
    }
}
