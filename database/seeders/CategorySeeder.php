<?php

namespace Database\Seeders;

use App\Models\Category;
use App\Models\Tenant;
use Illuminate\Database\Seeder;

class CategorySeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        Tenant::all()->each(function ($tenant) {
            $tenant->category()->saveMany([
                new Category(['name' => 'Food']),
                new Category(['name' => 'Drink']),
                new Category(['name' => 'Snack']),
                new Category(['name' => 'Dessert']),
            ]);
        });
    }
}
