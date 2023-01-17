<?php

namespace Database\Seeders;

use App\Models\PaymentMethod;
use App\Models\PgJmto;
use Carbon\Carbon;
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
        if (PaymentMethod::count() == 0) {
            PaymentMethod::insert([
                [
                    'name' => 'Mandiri Virtual Account',
                    'code_name' => 'pg_va_mandiri',
                    'code_sof' => 'MANDIRI',
                    'fee' => 1000,
                    'created_at' => Carbon::now()
                ],
                [
                    'name' => 'BRI Virtual Account',
                    'code_name' => 'pg_va_bri',
                    'code_sof' => 'BRI',
                    'fee' => 1000,
                    'created_at' => Carbon::now()
                ],
                [
                    'name' => 'BRI Direct Debit',
                    'code_name' => 'pg_dd_bri',
                    'code_sof' => 'BRI',
                    'fee' => 1000,
                    'created_at' => Carbon::now()
                ],
                [
                    'name' => 'LinkAja',
                    'code_name' => 'pg_link_aja',
                    'code_sof' => 'LinkAja',
                    'fee' => 1000,
                    'created_at' => Carbon::now()
                ],
                [
                    'name' => 'TAVQR',
                    'code_name' => 'tav_qr',
                    'code_sof' => 'TAVQR',
                    'fee' => 0,
                    'created_at' => Carbon::now()
                ],
                [
                    'name' => 'Cash',
                    'code_name' => 'cash',
                    'code_sof' => 'Cash',
                    'fee' => 0,
                    'created_at' => Carbon::now()
                ],
                [
                    'name' => 'BNI Virtual Account',
                    'code_name' => 'pg_va_bni',
                    'code_sof' => 'BNI',
                    'fee' => 1000,
                    'created_at' => Carbon::now()
                ],
                [
                    'name' => 'Kartu Debit / Kredit',
                    'code_name' => 'edc',
                    'code_sof' => 'EDC',
                    'fee' => 0,
                    'created_at' => Carbon::now()
                ],
            ]);
        }
    }
}
