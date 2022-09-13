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
                    'name' => 'Mandiri Virtual Account',
                    'code_name' => 'pg_va_mandiri',
                    'code_sof' => 'MANDIRI',
                    'fee' => 2000
                ],
                [
                    'name' => 'BRI Virtual Account',
                    'code_name' => 'pg_va_bri',
                    'code_sof' => 'BRI',
                    'fee' => 2000
                ],
                [
                    'name' => 'BRI Direct Debit',
                    'code_name' => 'pg_dd_bri',
                    'code_sof' => '',
                    'fee' => 2000
                ],
                [
                    'name' => 'LinkAja',
                    'code_name' => 'pg_link_aja',
                    'code_sof' => '',
                    'fee' => 2000
                ],
                [
                    'name' => 'QR',
                    'code_name' => 'tav_qr',
                    'code_sof' => '',
                    'fee' => 0
                ],
                [
                    'name' => 'Cash',
                    'code_name' => 'cash',
                    'code_sof' => '',
                    'fee' => 0
                ],
                [
                    'name' => 'BNI Virtual Account',
                    'code_name' => 'pg_va_bni',
                    'code_sof' => 'BNI',
                    'fee' => 2000
                ],
            ]);
        }
    }
}