<?php

namespace Database\Seeders;

use App\Models\Product;
use App\Models\Variant;
use Illuminate\Database\Seeder;

class VariantSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        Product::all()->each(function ($product) {
            $product->variant()->saveMany([
                new Variant([
                    'name' => 'Size',
                    'product_id' => $product->id,
                    'sub_variant' => [
                        [
                            'name' => 'Small',
                            'price' => 0,
                            'is_avail' => 1,
                        ],
                        [
                            'name' => 'Medium',
                            'price' => 5000,
                            'is_avail' => 0,

                        ],
                        [
                            'name' => 'Large',
                            'price' => 10000,
                            'is_avail' => 1,

                        ],
                    ]
                ]),
                new Variant([
                    'name' => 'Rasa',
                    'product_id' => $product->id,
                    'sub_variant' => [
                        [
                            'name' => 'Coklat',
                            'price' => 0,
                            'is_avail' => 1,
                        ],
                        [
                            'name' => 'Keju',
                            'price' => 0,
                            'is_avail' => 1,
                        ],
                    ]
                ])
            ]);
        });
    }
}
