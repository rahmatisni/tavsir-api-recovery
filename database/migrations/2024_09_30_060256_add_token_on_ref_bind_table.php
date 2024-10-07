<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddTokenOnRefBindTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('ref_bind', function (Blueprint $table) {
            $table->unsignedInteger('payment_method_id')->nullable();
            $table->text('token')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('ref_bind', function (Blueprint $table) {
            $table->dropColumn(['token', 'payment_method_id']);
        });
    }
}
