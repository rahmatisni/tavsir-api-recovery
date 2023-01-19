<?php

namespace Database\Seeders;

use App\Models\Business;
use App\Models\Ruas;
use App\Models\RestArea;
use App\Models\Tenant;
use App\Models\Supertenant;
use Illuminate\Database\Seeder;

class SupertenantSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        if (Supertenant::count() == 0) {
            Supertenant::insert([
                [
                    'name' => 'Super Tenant 00',
                    'business_id' => Business::all()->random()->id,
                    'ruas_id' => Ruas::all()->random()->id,
                    'category' => 'Food',
                    'address' => 'Jl. Raya Jawa Timur',
                    'latitude' => -6.91436,
                    'longitude' => 107.60981,
                    'rest_area_id' => RestArea::all()->random()->id,
                    'time_start' => '08:00',
                    'time_end' => '22:00',
                    'phone' => '+6281234567890',
                    'manager' => 'John Doe',
                    'photo_url' => 'https://picsum.photos/id/1/200/300',
                    'merchant_id' => 1,
                    'sub_merchant_id' => 1,
                    'is_open' => true,
                    'created_by' => 1,
                    'created_at' => now(),
                    'updated_at' => now()->addDay(),
                ],
                [
                    'name' => 'Super Tenant 88',
                    'business_id' => Business::all()->random()->id,
                    'ruas_id' => Ruas::all()->random()->id,
                    'category' => 'Food',
                    'address' => 'Jl. Raya Jawa Timur',
                    'latitude' => -6.91436,
                    'longitude' => 107.60981,
                    'rest_area_id' => RestArea::all()->random()->id,
                    'time_start' => '08:00',
                    'time_end' => '22:00',
                    'phone' => '+6281234567890',
                    'manager' => 'John Doe',
                    'photo_url' => 'https://picsum.photos/id/1/200/300',
                    'merchant_id' => 1,
                    'sub_merchant_id' => 1,
                    'is_open' => true,
                    'created_by' => 1,
                    'created_at' => now(),
                    'updated_at' => now()->addDay(),
                ],
                [
                    'name' => 'Super Tenant 99',
                    'business_id' => Business::all()->random()->id,
                    'ruas_id' => Ruas::all()->random()->id,
                    'category' => 'Drink',
                    'address' => 'Jl. Raya Jakarta',
                    'latitude' => -41.03622500,
                    'longitude' => 106.21695600,
                    'rest_area_id' => RestArea::all()->random()->id,
                    'time_start' => '08:00',
                    'time_end' => '22:00',
                    'phone' => '+6281234567890',
                    'manager' => 'Anna Doe',
                    'photo_url' => 'https://picsum.photos/id/1/200/300',
                    'merchant_id' => 1,
                    'sub_merchant_id' => 1,
                    'is_open' => true,
                    'created_by' => 1,
                    'created_at' => now(),
                    'updated_at' => now()->addDay(),
                ],
            ]);
        }
    }
}
