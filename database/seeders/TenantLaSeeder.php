<?php

namespace Database\Seeders;

use App\Models\TenantLa;
use Illuminate\Database\Seeder;

class TenantLaSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        TenantLa::insert([
            'tenant_id' => 1,
            'partner_mid' => '12345678910',
            'merchant_id' => '560556670138122',
            'merchant_pan' => '936009110020138122',
            'merchant_name' => 'JMTOQRDynamic',
            'merchant_criteria' => 'UME',
            'postal_code' => '12190',
            'city' => 'jakarta',
        ]);
    }
}
