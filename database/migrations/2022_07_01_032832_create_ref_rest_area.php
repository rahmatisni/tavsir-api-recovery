<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateRefRestArea extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('ref_rest_area', function (Blueprint $table) {
            $table->increments('id');
            $table->integer('ruas_id')->unsigned();
            $table->string('name');
            $table->string('address');
            $table->string('photo')->nullable();
            $table->decimal('latitude',10,8);
            $table->decimal('longitude',11,8);
            $table->string('time_start');
            $table->string('time_end');
            $table->boolean('is_open')->default(1);
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
        Schema::dropIfExists('ref_rest_area');
    }
}
