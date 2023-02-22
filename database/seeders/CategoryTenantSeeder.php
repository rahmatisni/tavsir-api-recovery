<?php

namespace Database\Seeders;

use App\Models\CategoryTenant;
use Illuminate\Database\Seeder;

class CategoryTenantSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        CategoryTenant::insert([
           ['name' => 'Food'],
           ['name' => 'Drink'],
           ['name' => 'Snack'],
           ['name' => 'Dessert'],
        ]);
    }
}
