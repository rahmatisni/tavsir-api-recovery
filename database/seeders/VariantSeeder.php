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
                    'detil' => [
                        [
                            'name' => 'Small',
                            'price' => 0,
                        ],
                        [
                            'name' => 'Medium',
                            'price' => 5000,
                        ],
                        [
                            'name' => 'Large',
                            'price' => 10000,
                        ],
                    ]
                ]),
                new Variant([
                    'name' => 'Rasa',
                    'product_id' => $product->id,
                    'detil' => [
                        [
                            'name' => 'Coklat',
                            'price' => 0,
                        ],
                        [
                            'name' => 'Keju',
                            'price' => 0,
                        ],
                    ]
                ])
            ]);
        });
    }
}
