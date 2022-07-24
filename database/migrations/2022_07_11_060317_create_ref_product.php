<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateRefProduct extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('ref_product', function (Blueprint $table) {
            $table->increments('id');
            $table->integer('tenant_id')->unsigned();
            $table->string('name');
            $table->string('sku');
            $table->string('category');
            $table->string('photo')->nullable();
            $table->decimal('discount', $precision = 15, $scale = 4);
            $table->decimal('price', $precision = 15, $scale = 4);
            //$table->string('price')->nullable();
            $table->boolean('is_active')->default(1);
            $table->string('description')->nullable();
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
        Schema::dropIfExists('ref_product');
    }
}
