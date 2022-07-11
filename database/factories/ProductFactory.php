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
        if(Tenant::count() == 0) {
            Tenant::factory()->count(10)->create();
        }
        $tenan = Tenant::all()->pluck('id')->toArray();
        return [
            'tenant_id' => array_rand($tenan),
            'category' => $this->faker->word,
            'name' => $this->faker->word,
            'photo_url' => $this->faker->imageUrl,
            'variant_id' => $this->faker->word,
            'variant_name' => $this->faker->word,
            'price' => $this->faker->word,
            'is_active' => $this->faker->boolean,
            'description' => $this->faker->word,
        ];
    }
}
