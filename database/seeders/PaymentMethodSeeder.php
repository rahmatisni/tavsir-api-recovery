<?php

namespace Database\Seeders;

use App\Models\PaymentMethod;
use App\Models\PgJmto;
use Carbon\Carbon;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Artisan;

class PaymentMethodSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        $data = [
            [
                'name' => 'Kartu Debit / Kredit',
                'code_name' => 'edc',
            ],
            [
                'name' => 'Cash',
                'code_name' => 'cash',
            ],
            [
                'name' => 'TAVQR',
                'code_name' => 'tav_qr',
            ],
            [
                'name' => 'LinkAja',
                'code_name' => 'pg_link_aja',
            ],
            [
                'name' => 'Mandiri Virtual Account',
                'code_name' => 'pg_va_mandiri',
                'code' => 'MANDIRI',
            ],
            [
                'name' => 'Mandiri Direct Debit',
                'code_name' => 'pg_dd_mandiri',
                'code' => 'MANDIRI',
            ],
            [
                'name' => 'BRI Virtual Account',
                'code_name' => 'pg_va_bri',
                'code' => 'BRI',
            ],
            [
                'name' => 'BRI Direct Debit',
                'code_name' => 'pg_dd_bri',
                'code' => 'BRI',
            ],
            [
                'name' => 'BNI Virtual Account',
                'code_name' => 'pg_va_bni',
                'code' => 'BNI',
            ],

        ];

        foreach ($data as $key => $value) {
            PaymentMethod::updateOrCreate($value);
        }
        Artisan::call('sof:sync');
    }
}
