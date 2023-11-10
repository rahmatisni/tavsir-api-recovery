<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class LogInformLa extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        //
        Schema::create('trans_cashbox', function (Blueprint $table) {
            $table->id();
            $table->string('trans_order_id');
            $table->string('data');
            $table->timestamps();
            $table->text('inquiry');
            $table->text('payment');
            $table->text('status');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('log_inform_la');

        //
    }
}
