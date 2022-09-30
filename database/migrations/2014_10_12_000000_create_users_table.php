<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateUsersTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('users', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('email')->unique();
            $table->string('role')->default('TENANT')->comment('CASHEER,TENANT, OWNER, JMRBAREA, JMRB, ADMIN, SUPERADMIN');
            $table->integer('business_id')->unsigned()->nullable();
            $table->integer('merchant_id')->unsigned()->nullable();
            $table->integer('sub_merchant_id')->unsigned()->nullable();
            $table->integer('tenant_id')->unsigned()->nullable();
            $table->integer('rest_area_id')->unsigned()->nullable();
            $table->integer('paystation_id')->unsigned()->nullable();
            $table->timestamp('email_verified_at')->nullable();
            $table->string('password');
            $table->string('pin')->nullable();
            $table->string('status')->default('ACTIVE')->comment('ACTIVE, INACTIVE');
            $table->rememberToken();
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
        Schema::dropIfExists('users');
    }
}
