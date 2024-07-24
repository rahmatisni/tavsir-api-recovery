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
            'nama_lengkap'=> $this->faker->name,
            'username' => $this->faker->userName,
            'customer_id' => 1,
            'phone' => $this->faker->phoneNumber,
            'balance' => $this->faker->numerify('1######'),
            'qr_code_use' => 0,
            'rest_area_id' => 1,
            'password' => $this->faker->uuid,
            'qr_code_image' => $this->faker->imageUrl,
            'is_active' => true,
            'public_key' => true,
            'hash' => $this->faker->uuid,
            'balance_history' => [
                "current_balance" => 152320,
                "data" => [
                    "trx_id" => "886405ad-6fa5-48a7-b6e2-0a6cdaefc09a",
                    "trx_order_id" => "4-17-POS-2023120509144983",
                    "trx_type" => "Belanja",
                    "trx_area" =>"Rest Area KM 88 B",
                    "trx_name" => "Samarina",
                    "trx_amount" => 55500,
                    "current_balance" => 152320,
                    "last_balance" => "207820",
                    "datetime" => "2023-12-05 09:15:15"
                ],
            ],
        ];
    }
}
