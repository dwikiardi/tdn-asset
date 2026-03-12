<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class UserSeeder extends Seeder
{
    public function run()
    {
        // =============================================
        // SUPER ADMIN — Akun login awal sistem
        // Username: admin | Password: admin123
        // WAJIB GANTI PASSWORD setelah login pertama!
        // =============================================
        User::updateOrCreate(
            ['username' => 'admin'],
            [
                'name'             => 'Administrator',
                'username'         => 'admin',
                'email'            => 'admin@dwiki-asset.local',
                'password'         => Hash::make('admin123'),
                'role'             => User::ROLE_SUPER_ADMIN,
                'is_active'        => true,
                'tridatu_user_id'  => null,
                'email_verified_at'=> now(),
            ]
        );

        // Operator contoh — untuk teknisi yang login dari Tridatu
        User::updateOrCreate(
            ['username' => 'operator'],
            [
                'name'             => 'Operator',
                'username'         => 'operator',
                'email'            => 'operator@dwiki-asset.local',
                'password'         => Hash::make('operator123'),
                'role'             => User::ROLE_OPERATOR,
                'is_active'        => true,
                'tridatu_user_id'  => null,
            ]
        );

        $this->command->info('✅ Admin user seeded: username=admin | password=admin123');
        $this->command->warn('⚠  Segera ganti password admin setelah login pertama!');
    }
}
