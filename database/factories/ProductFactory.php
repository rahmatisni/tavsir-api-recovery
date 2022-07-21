<?php

namespace Database\Factories;

use App\Models\Tenant;
use Illuminate\Database\Eloquent\Factories\Factory;

class ProductFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array
     */
    public function definition()
    {
        $faker = \Faker\Factory::create();
        $faker->addProvider(new \FakerRestaurant\Provider\en_US\Restaurant($faker));
        return [
            'tenant_id' => Tenant::all()->random()->id,
            'category' => $this->faker->word,
            'name' => $faker->foodName(),
            'photo' => $this->faker->imageUrl,
            'variant' => ['L', 'M', 'S'],
            'addon' => ['1', '2', '3'],
            'price' => $this->faker->numberBetween(1000, 20000),
            'is_active' => $this->faker->boolean,
            'description' => $this->faker->word,
        ];
    }
}
