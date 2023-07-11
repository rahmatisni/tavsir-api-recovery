<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateTransCashbox extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('trans_cashbox', function (Blueprint $table) {
            $table->increments('id');
            $table->integer('trans_operational_id')->unsigned();
            $table->integer('initial_cashbox')->unsigned();
            $table->integer('cashbox')->unsigned();
            $table->datetime('input_cashbox_date');
            $table->datetime('update_cashbox_date')->nullable();
            $table->integer('different_cashbox');
            $table->integer('pengeluaran_cashbox');
            $table->text('description')->nullable();
            $table->integer('rp_cash');
            $table->integer('rp_va_bri');
            $table->integer('rp_dd_bri');
            $table->integer('rp_va_mandiri');
            $table->integer('rp_va_bni');
            $table->integer('rp_tav_qr');
            $table->integer('rp_link_aja');
            $table->integer('rp_total');
            $table->integer('rp_addon_total')->default(0);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('trans_cashbox');
    }
}
