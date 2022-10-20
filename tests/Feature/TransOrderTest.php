<?php

namespace Tests\Feature;

use App\Models\User;
use Database\Factories\ProductFactory;
use Database\Seeders\UserSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Laravel\Passport\Passport;
use Tests\TestCase;

class TransOrderTest extends TestCase
{
    /**
     * A basic feature test example.
     *
     * @return void
     */
    public function test_user_cashier()
    {
        $user = User::factory()->cashier()->create();
        Passport::actingAs($user);
        $response = $this->get('/api/profile');
        $response->assertStatus(200)
                    ->assertJson([
                        'role' => User::CASHIER,
                    ]);
    }

    public function order_tavsir_pay_travoy()
    {
        $product = ProductFactory::new()->create();
        dd($product);
        $respone = $this->post('/api/tavsir/order',[

        ]);
    }
}
