<?php

namespace Database\Factories;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

class UserFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array
     */
    public function definition()
    {
        return [
            'name' => $this->faker->name(),
            'email' => $this->faker->unique()->safeEmail(),
            'email_verified_at' => now(),
            'password' => bcrypt('password'),
            'remember_token' => Str::random(10),
            'business_id' => BusinessFactory::new()->create()->id,
            'merchant_id' => 1,
            'sub_merchant_id' => 1,
            'rest_area_id' => RestAreaFactory::new()->create()->id,
            'tenant_id' => TenantFactory::new()->create()->id,
            'paystation_id' => PaystationFactory::new()->create()->id,
        ];
    }

    /**
     * Indicate that the model's email address should be unverified.
     *
     * @return \Illuminate\Database\Eloquent\Factories\Factory
     */
    public function unverified()
    {
        return $this->state(function (array $attributes) {
            return [
                'email_verified_at' => null,
            ];
        });
    }

    public function admin()
    {
        return $this->state(function (array $attributes) {
            return [
                'role' => User::ADMIN,
            ];
        });
    }

    public function cashier()
    {
        return $this->state(function (array $attributes) {
            return [
                'role' => User::CASHIER,
            ];
        });
    }

    public function tenant()
    {
        return $this->state(function (array $attributes) {
            return [
                'role' => User::TENANT,
            ];
        });
    }
}
