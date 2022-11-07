<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateRefBindTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('ref_bind', function (Blueprint $table) {
            $table->id();
            $table->string('customer_id');
            $table->string('sof_code');
            $table->string('refnum');
            $table->integer('bind_id')->unsigned()->nullable();
            $table->string('customer_name');
            $table->string('card_no');
            $table->string('phone');
            $table->string('email');
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
        Schema::dropIfExists('ref_bind');
    }
}
