<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateRefCustomize extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('ref_customize', function (Blueprint $table) {
            $table->id();
            $table->integer('tenant_id')->unsigned();
            $table->string('name');
            $table->text('pilihan')->nullable();

            $table->timestamps();
        });

        Schema::create('trans_product_customize', function (Blueprint $table) {
            $table->integer('product_id')->unsigned();
            $table->integer('customize_id')->unsigned();
            $table->boolean('must_choose')->default(true);
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('trans_product_customize');
        Schema::dropIfExists('ref_customize');
    }
}
