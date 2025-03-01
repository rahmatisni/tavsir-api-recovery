<?php

namespace Database\Factories;

use App\Models\RestArea;
use Illuminate\Database\Eloquent\Factories\Factory;

class PaystationFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array
     */
    public function definition()
    {
        return [
            'name' => $this->faker->name,
            'rest_area_id' => RestArea::all()->random()->id,
        ];
    }
}
