<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateTransSharing extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('trans_sharing', function (Blueprint $table) {
            $table->id();
            $table->unsignedInteger('trans_order_id');
            $table->string('order_type');
            $table->string('order_id');
            $table->unsignedInteger('payment_method_id');
            $table->string('payment_method_name');
            $table->unsignedInteger('sub_total');

            $table->unsignedInteger('pengelola_id');
            $table->unsignedInteger('persentase_pengelola');
            $table->unsignedInteger('total_pengelola');

            $table->unsignedInteger('supertenant_id')->nullable();
            $table->unsignedInteger('persentase_supertenant');
            $table->unsignedInteger('total_supertenant');

            $table->unsignedInteger('tenant_id')->nullable();
            $table->unsignedInteger('persentase_tenant');
            $table->unsignedInteger('total_tenant');

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
        Schema::dropIfExists('trans_sharing');
    }
}
