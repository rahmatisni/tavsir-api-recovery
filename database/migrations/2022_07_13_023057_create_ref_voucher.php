<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateRefVoucher extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('ref_voucher', function (Blueprint $table) {
            $table->increments('id');
            $table->string('nama_lengkap');
            $table->string('username');
            $table->uuid('voucher_uuid');
            $table->string('customer_id');
            $table->string('phone')->nullable();
            //$table->integer('trx_id')->unsigned();
            $table->string('balance');
            $table->string('balance_history');
            $table->string('qr_code_use');
            $table->string('qr_code_image');

            $table->string('auth_id');
            $table->integer('paystation_id')->unsigned()->nullable();
            $table->integer('created_by')->nullable();
            $table->timestamps();
            $table->integer('is_active');

        });

        Schema::create('ref_voucher_detail', function (Blueprint $table) {
            $table->increments('id');
            $table->integer('voucher_id')->unsigned();
            $table->integer('type')->unsigned();
            $table->integer('trx_id')->unsigned();
            $table->integer('trx_amount')->unsigned();
            $table->integer('current_balance')->unsigned();
            $table->integer('last_balance')->unsigned();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('ref_voucher_detail');
        Schema::dropIfExists('ref_voucher');
    }
}
