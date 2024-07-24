<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

class TransPaymentFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array
     */
    public function definition()
    {
        return [
            'data' => [],
            'inquiry' => [],
            'payment' => [],
        ];
    }
}
