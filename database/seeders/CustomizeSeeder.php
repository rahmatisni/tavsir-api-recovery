<?php

namespace Database\Seeders;

use App\Models\Customize;
use Illuminate\Database\Seeder;

class CustomizeSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        Customize::create([
            'tenant_id' => 1,
            'name' => 'Warna',
            'pilihan' => [
                [
                    'name' => 'Hitam',
                    'price' => 0,
                    'is_available' => 1
                ],
                [
                    'name' => 'Putih',
                    'price' => 0,
                    'is_available' => 1
                ]
            ]
        ]);
        Customize::create([
            'tenant_id' => 1,
            'name' => 'Ukuran',
            'pilihan' => [
                [
                    'name' => 'S',
                    'price' => 1000,
                    'is_available' => 1
                ],
                [
                    'name' => 'M',
                    'price' => 2000,
                    'is_available' => 1
                ],
                [
                    'name' => 'L',
                    'price' => 3000,
                    'is_available' => 0
                ]
            ]
        ]);
        Customize::create([
            'tenant_id' => 1,
            'name' => 'Rasa',
            'pilihan' => [
                [
                    'name' => 'Coklat',
                    'price' => 0,
                    'is_available' => 1
                ],
                [
                    'name' => 'Keju',
                    'price' => 1000,
                    'is_available' => 0
                ]
            ]
        ]);
    }
}
