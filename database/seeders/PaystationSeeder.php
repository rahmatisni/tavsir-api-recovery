<?php

namespace Database\Seeders;

use App\Models\Paystation;
use Illuminate\Database\Seeder;

class PaystationSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        Paystation::factory()->count(10)->create();
    }
}
