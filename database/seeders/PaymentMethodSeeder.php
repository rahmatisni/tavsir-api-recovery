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
                    'code_number' => '001',
                    'is_tavsir' => 0,
                ],
                [
                    'name' => 'BRI Virtual Account',
                    'code_name' => 'pg_va_bri',
                    'code_number' => '002',
                    'is_tavsir' => 0,
                ],
                [
                    'name' => 'BRI Direct Debit',
                    'code_name' => 'pg_dd_bri',
                    'code_number' => '003',
                    'is_tavsir' => 0,
                ],
                [
                    'name' => 'LinjAja',
                    'code_name' => 'pg_link_aja',
                    'code_number' => '004',
                    'is_tavsir' => 0,
                ],
                [
                    'name' => 'QR',
                    'code_name' => 'tav_qr',
                    'code_number' => '005',
                    'is_tavsir' => 0,
                ],
                [
                    'name' => 'Cash',
                    'code_name' => 'cash',
                    'code_number' => '006',
                    'is_tavsir' => 0,
                ],
                [
                    'name' => 'TAVQR',
                    'code_name' => 'tavqr',
                    'code_number' => '007',
                    'is_tavsir' => 1,
                ],
                [
                    'name' => 'DEBIT',
                    'code_name' => 'debit',
                    'code_number' => '008',
                    'is_tavsir' => 1,
                ],
                [
                    'name' => 'TUNAI',
                    'code_name' => 'tunai',
                    'code_number' => '009',
                    'is_tavsir' => 1,
                ],
            ]);
        }
    }
}