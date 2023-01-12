<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Business;

class BusinessSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        Business::updateOrCreate([
            'id' => '1',
        ], [
            'name' => 'JMRB',
            'email' => 'jmrb@jmto.co.id',
            'category' => 'jmrb',
            'address' => 'Gedung Jagorawi Lantai 2, Plaza Tol Taman Mini Indonesia Indah, RT.8/RW.2, Dukuh, Kec. Kramat jati, Kota Jakarta Timur, Daerah Khusus Ibukota Jakarta 13550',
            'status_perusahaan' => 'Persero',
            'latitude' => '-6.2927682',
            'longitude' => '106.8794075',
            'owner' => 'jmrb',
            'phone' => '02122093560',
        ]);
        Business::factory(10)->create();
    }
}
