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
                'name' => 'User Teknisi',
                'email' => 'teknisi@adminpay.com',
                'password' => bcrypt('password'),
                'role' => 'teknisi',
            ],
            [
                'name' => 'User Admin',
                'email' => 'admin@adminpay.com',
                'password' => bcrypt('password'),
                'role' => 'admin',
            ],
            [
                'name' => 'User Atasan',
                'email' => 'atasan@adminpay.com',
                'password' => bcrypt('password'),
                'role' => 'atasan',
            ],
            [
                'name' => 'User Owner',
                'email' => 'owner@adminpay.com',
                'password' => bcrypt('password'),
                'role' => 'owner',
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
