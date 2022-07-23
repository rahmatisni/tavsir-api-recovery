<?php

namespace Database\Seeders;

use App\Models\Tenant;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Artisan;

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
                'tenant_id' => Tenant::first()->id,
            ],
            [
                'name' => 'User',
                'email' => 'user@email.com',
                'role' => User::USER,
                'password' => bcrypt('password'),
                'tenant_id' => Tenant::first()->id,
            ]
        ]);
        Artisan::call('passport:install');
        Artisan::call('storage:link');
    }
}
