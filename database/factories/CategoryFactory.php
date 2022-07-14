<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use App\Models\Tenant;

class CategoryFactory extends Factory
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
        $tenant = Tenant::all()->pluck('id')->toArray();
        return [
            'tenant_id' => array_rand($tenant),
            'name' => $this->faker->name,
        ];
    }
}
