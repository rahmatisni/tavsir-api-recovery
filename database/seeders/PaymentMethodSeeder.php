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
                'name' => 'BRI Direct Debit SNAP',
                'is_snap' => 1,
                'code_name' => 'snap_dd_bri',
                'payment_method_code' => 'DD',
                'code' => 'BRI',
            ],
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
            [
                'name' => 'Virtual Account Mandiri',
                'code_name' => 'snap_va_mandiri',
                'sof_id' => '51105',
                'code' => 'MANDIRI',
                'description' => 'Bank Mandiri',
                'payment_method_id' => '51105',
                'payment_method_code' => 'VA',
                'logo' => 'images/DOntCFLHbI9iRn4lJxeXX2ERtc0Nl1PbTfTCvRxr.png',
                'integrator' => 'getoll',
            ],
            [
                'name' => 'BCA Virtual Account',
                'code_name' => 'midtrans_va_bca',
                'code' => 'BCA',
            ],
            [
                'name' => 'Card',
                'code_name' => 'midtrans_card',
                'code' => 'Card',
            ],
            [
                'name' => 'Gopay',
                'code_name' => 'midtrans_gopay',
                'code' => 'Qris',
            ],

        ];

        foreach ($data as $key => $value) {
            PaymentMethod::updateOrCreate($value);
        }
        // Artisan::call('sof:sync');
    }
}
