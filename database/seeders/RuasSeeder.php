<?php

namespace Database\Seeders;

use App\Models\Ruas;
use Illuminate\Database\Seeder;

class RuasSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        if(Ruas::count() == 0)
        {
            Ruas::insert([
                [
                    'name' => 'Jakarta - Bogor - Ciawi ( Jagorawi )',
                ],
                [
                    'name' => 'Cawang - Tomang - Cengkareng',
                ],
                [
                    'name' => 'Jakarta - Cikampek',
                ],
                [
                    'name' => 'Jakarta - Tangerang',
                ],
                [
                    'name' => 'Purwakarta - Bandung - Cileunyi',
                ],
                [
                    'name' => 'Palimanan - Kanci',
                ],
                [
                    'name' => 'Semarang',
                ],
                [
                    'name' => 'Surabaya - Gempol',
                ],
                [
                    'name' => 'Belawan - Medan - Tj Morawa',
                ],
            ]);
        }
    }
}
