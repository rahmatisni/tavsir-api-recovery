<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateTransOrderArsipTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('trans_order_arsip', function (Blueprint $table) {
            $table->id();
            $table->string('trans_order_id');
            $table->string('order_id');
            $table->string('order_type');
            $table->string('consume_type')->nullable();
            $table->string('nomor_name')->nullable();
            $table->integer('sub_total')->unsigned();
            $table->integer('fee')->unsigned();
            $table->integer('service_fee')->unsigned();
            $table->integer('total')->unsigned();
            $table->date('pickup_date')->nullable();
            $table->date('confirm_date')->nullable();
            $table->tinyInteger('rating')->nullable();
            $table->string('rating_comment')->nullable();
            $table->integer('business_id')->unsigned()->nullable();
            $table->integer('rest_area_id')->unsigned()->nullable();
            $table->integer('tenant_id')->unsigned()->nullable();
            $table->integer('supertenant_id')->unsigned()->nullable();
            $table->integer('merchant_id')->unsigned()->nullable();
            $table->integer('sub_merchant_id')->unsigned()->nullable();
            $table->string('paystation_id')->nullable();
            $table->string('customer_id')->nullable();
            $table->string('customer_name')->nullable();
            $table->integer('payment_method_id')->unsigned()->nullable();
            $table->integer('payment_id')->unsigned()->nullable();
            $table->integer('discount')->unsigned()->nullable();
            $table->string('casheer_id')->nullable();
            $table->integer('pay_amount')->unsigned()->nullable();
            $table->string('code_verif')->nullable();
            $table->string('status')->default('PENDING');
            $table->boolean('is_refund')->default(0);
            $table->string('canceled_by')->nullable();
            $table->string('canceled_name')->nullable();
            $table->string('reason_cancel')->nullable();
            $table->integer('voucher_id')->nullable();
            $table->integer('id_ops')->nullable();
            $table->integer('saldo_qr')->unisgned()->nullable();
            $table->string('description')->nullable();
            $table->integer('harga_kios')->nullable();
            $table->date('settlement_at')->nullable();
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
        Schema::dropIfExists('trans_order_arsip');
    }
}
