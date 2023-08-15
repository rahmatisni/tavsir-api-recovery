<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateRefTenant extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('ref_tenant', function (Blueprint $table) {
            $table->increments('id');
            $table->integer('supertenant_id')->unsigned()->nullable();
            $table->integer('business_id')->unsigned();
            $table->integer('ruas_id')->unsigned();
            $table->string('name');
            $table->integer('category_tenant_id')->unsigned();
            $table->string('address')->nullable();
            $table->decimal('latitude', 10, 8);
            $table->decimal('longitude', 11, 8);
            $table->integer('rest_area_id')->unsigned();
            $table->string('time_start');
            $table->string('time_end');
            $table->string('phone')->nullable();
            $table->string('email')->nullable();
            $table->string('manager')->nullable();
            $table->string('photo_url')->nullable();
            $table->integer('merchant_id')->unsigned()->default(169);
            $table->integer('sub_merchant_id')->unsigned()->nullable();
            $table->boolean('is_open')->default(1);
            $table->boolean('is_verified')->default(0);
            $table->boolean('in_takengo')->default(1);
            $table->integer('subscription_id')->unsigned()->nullable();
            $table->boolean('is_subscription')->default(0);
            $table->integer('kuota_kasir')->unsigned()->default(0);
            $table->boolean('is_print')->default(0);


            $table->integer('created_by')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('ref_tenant');
    }
}
