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
                'business_id' => 1,
                'merchant_id' => 1,
                'sub_merchant_id' => 1,
                'tenant_id' => Tenant::first()->id,
                'rest_area_id' => 0,
                'paystation_id' => 1,
            ],
            [
                'name' => 'User',
                'email' => 'user@email.com',
                'role' => User::USER,
                'password' => bcrypt('password'),
                'business_id' => 1,
                'merchant_id' => 1,
                'sub_merchant_id' => 1,
                'tenant_id' => Tenant::first()->id,
                'rest_area_id' => 0,
                'paystation_id' => 1,
            ],
            [
                'name' => 'Paystation Rest Area KM 149 B',
                'email' => 'paystation@email.com',
                'role' => User::PAYSTATION,
                'password' => bcrypt('password'),
                'business_id' => 0,
                'merchant_id' => 0,
                'sub_merchant_id' => 0,
                'tenant_id' => 0,
                'rest_area_id' => 1,
                'paystation_id' => 0,
            ],
            [
                'name' => 'JMRB',
                'email' => 'jmrb@email.com',
                'role' => User::JMRB,
                'password' => bcrypt('password'),
                'business_id' => 1,
                'merchant_id' => 0,
                'sub_merchant_id' => 0,
                'tenant_id' => 0,
                'rest_area_id' => 0,
                'paystation_id' => 0,
            ]
        ]);
        Artisan::call('passport:install');
        Artisan::call('storage:link');
    }
}
