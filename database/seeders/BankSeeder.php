<?php

namespace Database\Seeders;

use App\Models\Bank;
use App\Models\PgJmto;
use Carbon\Carbon;
use Illuminate\Database\Seeder;

class BankSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        Bank::updateOrCreate(
            ['id' => 1],
            ['name' => 'Bank DKI',],
        );
        Bank::updateOrCreate(
            ['id' => 2],
            ['name' => 'Bank Jago',],
        );
        Bank::updateOrCreate(
            ['id' => 3],
            ['name' => 'Bank Permata',],
        );
        Bank::updateOrCreate(
            ['id' => 4],
            ['name' => 'Bank Jenius',],
        );
        Bank::updateOrCreate(
            ['id' => 5],
            ['name' => 'Bank BTN',],
        );
        Bank::updateOrCreate(
            ['id' => 6],
            ['name' => 'Bank Danamom',],
        );
        Bank::updateOrCreate(
            ['id' => 7],
            ['name' => 'Bank CMB Niaga',],
        );
        Bank::updateOrCreate(
            ['id' => 8],
            ['name' => 'Bank UCB',],
        );
        Bank::updateOrCreate(
            ['id' => 9],
            ['name' => 'Bank OCBC',],
        );
        Bank::updateOrCreate(
            ['id' => 10],
            ['name' => 'Bank Mega',],
        );
        Bank::updateOrCreate(
            ['id' => 11],
            ['name' => 'Bank Muamalat',],
        );
    }
}
