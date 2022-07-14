<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

class VoucherFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array
     */
    public function definition()
    {
        return [
            'voucher_uuid' => $this->faker->uuid,
        'customer_id' => $this->faker->word,
        'phone' => $this->faker->phoneNumber,
        //'trx_id',
        'balance' => $this->faker->randomDigit,
        'qr' => $this->faker->imageUrl,
        'auth_id' => $this->faker->word,
        'paystation_id' => $this->faker->numberBetween(1, 10),
        ];
    }
}
