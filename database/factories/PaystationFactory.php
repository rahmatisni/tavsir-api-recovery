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
        if(RestArea ::count() == 0) {
            RestArea::factory()->count(10)->create();
        }
        $restarea = RestArea::all()->pluck('id')->toArray();
        return [
            'name' => $this->faker->name,
            'rest_area_id' => array_rand($restarea),
        ];
    }
}
