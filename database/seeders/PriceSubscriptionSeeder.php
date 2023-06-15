<?php

namespace Database\Seeders;

use App\Models\PriceSubscription;
use Illuminate\Database\Seeder;

class PriceSubscriptionSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        PriceSubscription::insert([
            'price_tenant' => 1000000,
            'price_cashier' => 1000000
        ]);
    }
}
