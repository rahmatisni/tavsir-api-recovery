<?php

namespace Database\Seeders;

use App\Models\RestArea;
use Illuminate\Database\Seeder;

class RestAreaSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        if(RestArea::count() == 0)
        {
            RestArea::insert([
                [
                    'name' => 'Rest Area KM 149 B',
                    'address' => 'Jl. Raya Bandung - Garut, Tegalluar, Kec. Bojongsoang, Kota Bandung, Jawa Barat 40287',
                    'latitude' => -6.966562,
                    'longitude' => 107.7124337,
                    'time_start' => '08:00',
                    'time_end' => '22:00',
                    'is_open' => true
                ],
                [
                    'name' => 'Rest Area KM 149 B',
                    'address' => 'Derwati, Kec. Rancasari, Kota Bandung, Jawa Barat 40292',
                    'latitude' => -6.9688408,
                    'longitude' => 107.6883776,
                    'time_start' => '08:00',
                    'time_end' => '22:00',
                    'is_open' => true
                ],
                [
                    'name' => 'Rest Area KM 125',
                    'address' => '4G79+VP5, Cibeber, Kec. Cimahi Sel., Kota Cimahi, Jawa Barat 40531',
                    'latitude' => -6.8835144,
                    'longitude' => 107.5132386,
                    'time_start' => '08:00',
                    'time_end' => '22:00',
                    'is_open' => true
                ],
            ]);
        }
    }
}
