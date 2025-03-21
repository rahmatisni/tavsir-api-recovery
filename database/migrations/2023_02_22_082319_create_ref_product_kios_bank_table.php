<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateRefProductKiosBankTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('ref_product_kios_bank', function (Blueprint $table) {
            $table->id();
            $table->string('kategori');
            $table->string('sub_kategori');
            $table->string('kode')->unique();
            $table->string('name');
            $table->unsignedInteger('prefix_id')->nullable();
            $table->string('harga')->nullable();
            $table->timestamps();
            $table->unsignedInteger('is_active')->nullable();
            $table->string('integrator')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('ref_product_kios_bank');
    }
}
