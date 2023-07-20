<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateRefBusiness extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('ref_business', function (Blueprint $table) {
            $table->increments('id');
            $table->string('name');
            $table->string('email')->nullable();
            $table->string('category');
            $table->string('status_perusahaan');
            $table->string('address');
            $table->decimal('latitude',10,8);
            $table->decimal('longitude',11,8);
            $table->string('owner');
            $table->string('phone');
            $table->integer('created_by')->nullable();
            $table->date('subscription_end')->nullable();
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
        Schema::dropIfExists('ref_business');
    }
}
