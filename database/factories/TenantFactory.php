<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

class TenantFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array
     */
    public function definition()
    {
        return [
            'business_id' => $this->faker->numberBetween(1, 10),
            'name' => $this->faker->name,
            'category' => $this->faker->word,
            'address' => $this->faker->address,
            'latitude' => $this->faker->latitude,
            'longitude' => $this->faker->longitude,
            'rest_area_id' => $this->faker->numberBetween(1, 10),
            'time_start' => $this->faker->time('H:i'),
            'time_end' => $this->faker->time('H:i'),
            'phone' => $this->faker->phoneNumber,
            'manager' => $this->faker->name,
            'photo_url' => $this->faker->imageUrl,
            'merchant_id' => $this->faker->numberBetween(1, 10),
            'sub_merchant_id' => $this->faker->numberBetween(1, 10),
            'is_open' => $this->faker->boolean,
            'created_by' => $this->faker->numberBetween(1, 10),
            'created_at' => $this->faker->dateTime,
            'updated_at' => $this->faker->dateTime,
        ];
    }
}
