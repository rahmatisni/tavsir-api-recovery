<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateRefPaymentMethod extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('ref_payment_method', function (Blueprint $table) {
            $table->increments('id');
            $table->string('code_name');
            $table->unsignedInteger('sof_id')->nullable();
            $table->string('code')->nullable();
            $table->string('name');
            $table->string('description')->nullable();
            $table->unsignedInteger('payment_method_id')->nullable();
            $table->string('payment_method_code')->nullable();
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
        Schema::dropIfExists('ref_payment_method');
    }
}
