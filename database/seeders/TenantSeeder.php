<?php

namespace Database\Seeders;

use App\Models\Business;
use App\Models\Ruas;
use App\Models\RestArea;
use App\Models\Tenant;
use Illuminate\Database\Seeder;

class TenantSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        if(Tenant::count() == 0)
        {
            Tenant::insert([
                [
                    'name' => 'Rumah Talas',
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
                    'name' => 'Drink Sweet',
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
                [
                    'name' => 'Pecel Sedap',
                    'business_id' => Business::all()->random()->id,
                    'ruas_id' => Ruas::all()->random()->id,
                    'category' => 'Food',
                    'address' => 'Jl. Raya Bandung',
                    'latitude' => 35.47488000,
                    'longitude' => 60.51634100,
                    'rest_area_id' => RestArea::all()->random()->id,
                    'time_start' => '08:00',
                    'time_end' => '22:00',
                    'phone' => '+6281234567890',
                    'manager' => 'Jak Doe',
                    'photo_url' => 'https://picsum.photos/id/1/200/300',
                    'merchant_id' => 1,
                    'sub_merchant_id' => 1,
                    'is_open' => true,
                    'created_by' => 1,
                    'created_at' => now(),
                    'updated_at' => now()->addDay(),
                ],
            ]);
            Tenant::all()->each(function($tenant) {
                $tenant->saldo()->create([
                    'saldo' => 0,
                    'rest_area_id' => $tenant->rest_area_id,
                ]);
            });
        }
    }
}
