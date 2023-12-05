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
        Schema::table('trans_cashbox', function (Blueprint $table) {
            $table->string('trans_order_id');
            $table->string('data');
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
        Schema::table('trans_cashbox', function (Blueprint $table) {
            $table->dropColumn(['trans_order_id','data','inquiry','payment','status']);
        });
    }
}
