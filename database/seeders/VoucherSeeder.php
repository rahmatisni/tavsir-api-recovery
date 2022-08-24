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
        $restarea = RestArea::first();

        Voucher::create([
            'nama_lengkap' => 'Admin',
            'username' => '1-08123456789',
            'password' => bcrypt('password'),
            'customer_id' => '939412828407556',
            'phone' => '08123456789',
            'balance' => '300000',
            'qr_code_use' => 1,
            'qr_code_image' => 'qr_code_939412828407556.png',
            'is_active' => '1',
            'public_key' => '018754839921632',
            'hash' => 'hRV/12TBXGURXT1vH4yy',
            'balance_history' => [
                "current_balance" => 300000,
                "data" => [
                    [
                        "trx_id" => "abcd1236",
                        "trx_order_id" => "REFUND-2022082313023",
                        "trx_type" => 'Refund',
                        "trx_area" => 'Pay Station '.$restarea->name,
                        "trx_name" => 'TAVQR',
                        "trx_amount" => "100000",
                        "current_balance"=>"300000",
                        "last_balance"=>400000,
                        "datetime"=>"2022-08-23 15:22:45"
                    ],
                    [
                        "trx_id" => "abcd1235",
                        "trx_order_id" => "TNG-20220822130914",
                        "trx_type" => 'Belanja',
                        "trx_area" => $restarea->name,
                        "trx_name" => 'Rumah Talas',
                        "trx_amount" => "100000",
                        "current_balance" => "400000",
                        "last_balance" => 500000,
                        "datetime"=>"2022-08-21 12:31:11"
                    ],
                    [
                        "trx_id" => "abcd1234",
                        "trx_order_id" => "INIT TOPUP-939412828407556-1661146885",
                        "trx_type" => 'Top Up',
                        "trx_area" => 'Pay Station '.$restarea->name,
                        "trx_name" => 'TAVQR',
                        "trx_amount" => "500000",
                        "current_balance"=>"500000",
                        "last_balance"=>0,
                        "datetime"=>"2022-08-21 05:41:25"
                    ],
                ]
            ],
            'rest_area_id' => $restarea->id
        ]);

    }
}
