<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateTransStock extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('trans_stock', function (Blueprint $table) {
            $table->id();
            $table->integer('product_id')->unisgned();
            $table->enum('stock_type', ['init', 'in', 'out'])->comment('init, in, out');
            $table->integer('recent_stock')->unisgned();
            $table->integer('stock_amount')->unisgned();
            $table->string('keterangan')->nullable();
            $table->integer('created_by')->unisgned();
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
        Schema::dropIfExists('trans_stock');
    }
}
