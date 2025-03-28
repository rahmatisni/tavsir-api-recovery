<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

class BusinessFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array
     */
    public function definition()
    {
        $status = ['Perseorangan','Persero'];
        $key = array_rand($status);
        return [
            'name' => $this->faker->company,
            'email' => $this->faker->email,
            'status_perusahaan' => $status[$key],
            'category' => $this->faker->word,
            'address' => $this->faker->address,
            'latitude' => $this->faker->latitude,
            'longitude' => $this->faker->longitude,
            'owner' => $this->faker->name,
            'phone' => $this->faker->phoneNumber,
        ];
    }
}
