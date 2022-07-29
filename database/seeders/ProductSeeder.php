<?php

namespace Database\Seeders;

use App\Models\Customize;
use App\Models\Product;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class ProductSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        Product::factory()->count(10)->create()->each(function ($product) {
            $product->customize()->sync([
                'customize_id' => Customize::all()->random()->id,
                'must_choose' => true,
            ]);
        });
    }
}
