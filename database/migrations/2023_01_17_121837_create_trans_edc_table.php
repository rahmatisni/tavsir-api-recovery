<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateTransEdcTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('trans_edc', function (Blueprint $table) {
            $table->id();
            $table->unsignedInteger('trans_order_id');
            $table->unsignedInteger('bank_id');
            $table->string('card_nomor');
            $table->string('ref_nomor');
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
        Schema::dropIfExists('trans_edc');
    }
}
