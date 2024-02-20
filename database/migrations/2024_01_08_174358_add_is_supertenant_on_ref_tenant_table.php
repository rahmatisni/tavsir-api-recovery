<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddIsSupertenantOnRefTenantTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('ref_tenant', function (Blueprint $table) {
            $table->boolean('is_supertenant')->default(0);
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
                'is_supertenant',
            ]);
        });
    }
}
