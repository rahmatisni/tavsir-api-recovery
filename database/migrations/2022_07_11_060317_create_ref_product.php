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
            $table->string('variant')->nullable()->comment('Array');
            $table->string('addon')->nullable()->comment('Array');
            $table->string('price')->nullable();
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
