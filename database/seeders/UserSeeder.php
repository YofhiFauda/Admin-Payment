<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;

class UserSeeder extends Seeder
{
    public function run(): void
    {
        $users = [
            [
                'name' => 'Super Admin',
                'email' => 'superadmin@whusnet.com',
                'password' => bcrypt('superadmin'),
                'role' => 'owner',
            ],
            [
                'name' => 'Atasan',
                'email' => 'atasan@whusnet.com',
                'password' => bcrypt('password'),
                'role' => 'atasan',
            ],
            [
                'name' => 'Admin',
                'email' => 'admin@whusnet.com',
                'password' => bcrypt('password'),
                'role' => 'admin',
            ],
            [
                'name' => 'Teknisi',
                'email' => 'teknisi@whusnet.com',
                'password' => bcrypt('password'),
                'role' => 'teknisi',
            ],
        ];
        foreach ($users as $userData) {
            User::firstOrCreate(
                ['email' => $userData['email']],
                $userData
            );
        }
    }
}
