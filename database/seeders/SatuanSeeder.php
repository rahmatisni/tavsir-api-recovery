<?php

namespace Database\Seeders;

use App\Models\Satuan;
use Illuminate\Database\Seeder;

class SatuanSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        $payload = [
            //Berat
            [
                'type' => Satuan::berat,
                'name' => 'gr',
            ],
            [
                'type' => Satuan::berat,
                'name' => 'kg',
            ],
            [
                'type' => Satuan::berat,
                'name' => 'ons',
            ],
            [
                'type' => Satuan::berat,
                'name' => 'ton',
            ],

            //Volume
            [
                'type' => Satuan::volume,
                'name' => 'liter',
            ],
            [
                'type' => Satuan::volume,
                'name' => 'ml',
            ],

            //Unit
            [
                'type' => Satuan::unit,
                'name' => 'pcs',
            ],
            [
                'type' => Satuan::unit,
                'name' => 'porsi',
            ],
            [
                'type' => Satuan::unit,
                'name' => 'buah',
            ],
            [
                'type' => Satuan::unit,
                'name' => 'piring',
            ],
            [
                'type' => Satuan::unit,
                'name' => 'cup',
            ],
        ];
        foreach ($payload as $key => $value) {
            Satuan::updateOrCreate($value,$value);
        }
    }
}
