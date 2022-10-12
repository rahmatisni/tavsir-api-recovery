<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateTransInvoice extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('trans_invoice', function (Blueprint $table) {
            $table->id();
            $table->integer('trans_saldo_id')->unsigned();
            $table->string('invoice_id');
            $table->integer('cashier_id')->unsigned();
            $table->integer('pay_station_id')->unsigned();
            $table->integer('nominal')->unsigned();
            $table->dateTime('claim_date');
            $table->dateTime('paid_date')->nullable();
            $table->string('status')->default('UNPAID')->coment('UNPAID, PAID');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('trans_invoice');
    }
}
