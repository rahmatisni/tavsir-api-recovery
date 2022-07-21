<?php

namespace Database\Seeders;

use App\Models\PaymentMethod;
use Illuminate\Database\Seeder;

class PaymentMethodSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        if(PaymentMethod::count() == 0){
            PaymentMethod::insert([
                [
                    'name' => 'tavqr',
                    'code' => '001',
                ],
                [
                    'name' => 'pg_va_bri',
                    'code' => '002',
                ],
                [
                    'name' => 'pg_dd+bri',
                    'code' => '003',
                ],
                [
                    'name' => 'pg_link_aja',
                    'code' => '004',
                ]
            ]);
        }
    }
}