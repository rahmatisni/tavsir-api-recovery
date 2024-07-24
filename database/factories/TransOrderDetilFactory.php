<?php

namespace Database\Factories;

use App\Models\PaymentMethod;
use App\Models\Tenant;
use App\Models\TransOrder;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class TransOrderDetilFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array
     */
    public function definition()
    {
        return [
            'trans_order_id' => 1,
            'product_id' => 1,
            'product_name' => $this->faker->name,
            'base_price' => $this->faker->numerify('1#####'),
            'price' => $this->faker->numerify('1#####'),
            'qty' => 1,
            'total_price' => $this->faker->numerify('1#####'),
        ];
    }
}
