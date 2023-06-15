<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     *
     * @return void
     */
    public function run()
    {
        $this->call(RuasSeeder::class);
        $this->call(BusinessSeeder::class);
        $this->call(RestAreaSeeder::class);
        $this->call(PaystationSeeder::class);
        $this->call(SupertenantSeeder::class);
        $this->call(CategoryTenantSeeder::class);
        $this->call(TenantSeeder::class);
        $this->call(UserSeeder::class);
        $this->call(CategorySeeder::class);
        $this->call(CustomizeSeeder::class);
        $this->call(ProductSeeder::class);
        $this->call(BankSeeder::class);
        $this->call(PaymentMethodSeeder::class);
        $this->call(VoucherSeeder::class);
        $this->call(ProductKiosBankSeeder::class);
        $this->call(PriceSubscriptionSeeder::class);
    }
}
