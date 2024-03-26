<?php

namespace Tests;

use App\Models\User;
use Illuminate\Foundation\Testing\TestCase as BaseTestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Illuminate\Support\Facades\Artisan;
use Laravel\Passport\Passport;

abstract class TestCase extends BaseTestCase
{
    use CreatesApplication;
    use RefreshDatabase;
    use WithFaker;

    protected function setUp(): void
    {
        parent::setUp();
        
        Artisan::call('migrate:fresh --seed');
    }

    public function loginAs($user = null)
    {
        if(!$user){
            $user = User::first() ?? User::factory()->create();;
        }
        Passport::actingAs(
            $user,
            ['*']
        );
        return $user;
    }

    public function generateCustomer()
    {
        $data = [
            'customer_email' => $this->faker()->email,
            'customer_name' => $this->faker()->name,
            'customer_phone' => $this->faker()->numerify('08##########'),
        ];

        return $data;
    }
}
