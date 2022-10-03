<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateTransOperational extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('trans_operational', function (Blueprint $table) {
            $table->increments('id');
            $table->integer('tenant_id');
            $table->integer('periode')->unsigned()->nullable();
            $table->integer('casheer_id')->unsigned();
            $table->dateTime('start_date');
            $table->dateTime('end_date')->nullable();
            $table->integer('duration')->unsigned();
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
        Schema::dropIfExists('trans_operasional');
    }
}
