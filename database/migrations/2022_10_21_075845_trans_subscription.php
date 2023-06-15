<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class TransSubscription extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('trans_subscription', function (Blueprint $table) {
            $table->id();
            $table->string('id_activation');
            $table->string('type');
            $table->integer('super_merchant_id')->unsigned();
            $table->integer('masa_aktif')->unsigned();
            $table->integer('limit_cashier')->unsigned();
            $table->integer('limit_tenant')->unsigned();
            $table->string('document_type')->nullable();
            $table->string('file')->nullable();

            $table->integer('price_tenant')->unsigned()->nullable();
            $table->integer('price_cashier')->unsigned()->nullable();
            $table->integer('price_total')->unsigned()->nullable();
            $table->string('detail_aktivasi')->nullable();

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
        Schema::dropIfExists('trans_subscription');
    }
}
