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
            $table->string('category');
            $table->string('photo_url')->nullable();
            $table->string('variant_id')->nullable()->comment('Array');
            $table->string('variant_name')->nullable()->comment('Array');
            $table->string('price')->nullable()->comment('Array');
            $table->boolean('is_active')->default(1);
            $table->text('description')->nullable();
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
