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
            $table->integer('business_id')->unsigned();
            $table->integer('masa_aktif')->unsigned();
            $table->integer('limit_tenant')->unsigned();
            $table->integer('limit_cashier')->unsigned();
            $table->string('status')->default('ACTIVE');

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
