<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;

class UserSeeder extends Seeder
{
    public function run(): void
    {
        $users = [
            // [
            //     'name' => 'Budi Santoso Handoko Wijoyo',
            //     'email' => 'teknisi@whusnet.com',
            //     'password' => bcrypt('password'),
            //     'role' => 'teknisi',
            // ],
            // [
            //     'name' => 'Naufal Khoirul Anam',
            //     'email' => 'admin@whusnet.com',
            //     'password' => bcrypt('password'),
            //     'role' => 'admin',
            // ],
            
            [
                'name' => 'Super Admin',
                'email' => 'superadmin@whusnet.com',
                'password' => bcrypt('superadmin'),
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
