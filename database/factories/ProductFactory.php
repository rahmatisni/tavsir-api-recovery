<?php

namespace Database\Factories;

use App\Models\Tenant;
use App\Models\Category;
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
        $tenant = Tenant::all()->random();
        return [
            'tenant_id' => $tenant->id,
            'category_id' => Category::where('tenant_id',$tenant->id)->get()->random()->id,
            'name' => $faker->foodName(),
            'sku' => 'P-'.$faker->unique()->numberBetween(1,9999),
            'photo' => $this->faker->imageUrl,
            'price' => 10000,
            'is_active' => $this->faker->boolean,
            'description' => $this->faker->word,
        ];
    }
}
