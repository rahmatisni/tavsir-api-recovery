<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateTransChat extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('trans_operasional', function (Blueprint $table) {
            $table->increments('id_ops');
            $table->int('tenant_id');
            $table->int('perioda');
            $table->string('casheer_id');
            $table->int('tenant_id');
            $table->dateTime('tgl_sop');
            $table->dateTime('tgl_eop');
            $table->int('durasi_operasi');
            $table->timestamp('created_at');
            $table->timestamp('updated_at');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('trans_operasional');
    }
}
