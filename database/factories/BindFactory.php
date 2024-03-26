<?php

namespace Database\Factories;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Factories\Factory;

class BindFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array
     */
    public function definition()
    {
        return [
            'customer_id' => 1,
            'bind_id' => $this->faker->numerify('######'),
            'sof_code' => 'mandiri',
            'customer_name' => $this->faker->name,
            'card_no' => $this->faker->numerify('################'),
            'phone' => $this->faker->phoneNumber,
            'email' => $this->faker->email,
            'refnum' => $this->faker->asciify('************'),
            'exp_date' => Carbon::now()->addDays(3),
        ];
    }
}
