<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddInfoResiOnRefTenantTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('ref_tenant', function (Blueprint $table) {
            $table->string('logo')->nullable();
            $table->string('additional_information', 50)->nullable();
            $table->string('instagram', 50)->nullable();
            $table->string('facebook', 50)->nullable();
            $table->string('website', 50)->nullable();
            $table->string('note', 120)->nullable();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('ref_tenant', function (Blueprint $table) {
            $table->dropColumn([
                'logo',
                'additional_information',
                'instagram',
                'facebook',
                'website',
                'note'
            ]);
        });
    }
}
