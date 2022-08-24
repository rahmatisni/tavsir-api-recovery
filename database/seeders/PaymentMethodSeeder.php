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
                    'code_sof' => 'MANDIRI'
                ],
                [
                    'name' => 'BRI Virtual Account',
                    'code_name' => 'pg_va_bri',
                    'code_sof' => 'BRI'
                ],
                [
                    'name' => 'BRI Direct Debit',
                    'code_name' => 'pg_dd_bri',
                    'code_sof' => ''
                ],
                [
                    'name' => 'LinkAja',
                    'code_name' => 'pg_link_aja',
                    'code_sof' => ''
                ],
                [
                    'name' => 'QR',
                    'code_name' => 'tav_qr',
                    'code_sof' => ''
                ],
                [
                    'name' => 'Cash',
                    'code_name' => 'cash',
                    'code_sof' => ''
                ],
                [
                    'name' => 'BNI Virtual Account',
                    'code_name' => 'pg_va_bni',
                    'code_sof' => 'BNI'
                ],
            ]);
        }
    }
}