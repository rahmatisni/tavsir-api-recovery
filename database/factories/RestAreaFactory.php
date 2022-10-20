<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

class RestAreaFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array
     */
    public function definition()
    {
        return [
            'name' => $this->faker->unique()->word,
            'address' => $this->faker->address,
            'latitude' => $this->faker->latitude,
            'longitude' => $this->faker->longitude,
            'ruas_id' => RuasFactory::new()->create()->id,
            'time_start' => '08:00',
            'time_end' => '20:00',
            'is_open' => $this->faker->boolean(50)
        ];
    }
}
