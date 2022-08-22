<?php

namespace Database\Seeders;

use App\Models\RestArea;
use App\Models\Voucher;
use Illuminate\Database\Seeder;

class VoucherSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        Voucher::create([
            'nama_lengkap' => 'Admin',
            'username' => 'admin@email.com',
            'customer_id' => '939412828407556',
            'phone' => '12345678',
            'balance' => '300000',
            'qr_code_use' => 1,
            'qr_code_image' => 'qr_code_939412828407556.png',
            'is_active' => '1',
            'public_key' => '018754839921632',
            'hash' => 'hRV/12TBXGURXT1vH4yy',
            'balance_history' => [
                "current_balance" => "300000",
                "data" => [
                    [
                        "trx_id" => "INIT TOPUP-939412828407556-1661146885",
                        "trx_amount" => "300000",
                        "current_balance"=>"300000",
                        "last_balance"=>0,
                        "datetime"=>"2022-08-22 05:41:25"
                    ]
                ]
            ],
            'rest_area_id' => RestArea::all()->random()->id
        ]);

    }
}
