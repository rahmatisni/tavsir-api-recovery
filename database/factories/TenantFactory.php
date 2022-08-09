<?php

namespace Database\Factories;

use App\Models\Business;
use App\Models\RestArea;
use App\Models\Ruas;
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
        $restarea = RestArea::pluck('id')->toArray();
        $k = array_rand($restarea);
        $restarea_id = $restarea[$k];

        $business = Business::pluck('id')->toArray();
        $l = array_rand($restarea);
        $business_id = $restarea[$l];
        
        return [
            'business_id' => $business_id,
            'ruas_id' => Ruas::all()->random()->id,
            'name' => $this->faker->name,
            'category' => $this->faker->word,
            'address' => $this->faker->address,
            'latitude' => $this->faker->latitude,
            'longitude' => $this->faker->longitude,
            'rest_area_id' => $restarea_id,
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
