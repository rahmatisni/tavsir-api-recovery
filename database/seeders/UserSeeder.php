<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;

class UserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        User::insert([
            [
                'name' => 'Admin',
                'email' => 'admin@email.com',
                'role' => User::ADMIN,
                'password' => bcrypt('password'),
            ],
            [
                'name' => 'User',
                'email' => 'user@email.com',
                'role' => User::USER,
                'password' => bcrypt('password'),
            ]
        ]);
    }
}
