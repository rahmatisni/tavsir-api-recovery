<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateTransCashbox extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('trans_chashbox', function (Blueprint $table) {
            $table->increments('id');
            $table->integer('cashbox');
            $table->integer('cashbox_old');
            $table->integer('periode')->unsigned();
            $table->date('tgl_ubah_cashbox');
            $table->date('tgl_input_cashbox');
            $table->integer('id_ops');
            $table->string('casheer_id');
            $table->integer('tenant_id');
            $table->integer('selisih_cashbox');
            $table->integer('uang_pengeluaran');
            $table->text('ket_pengeluaran');
            $table->integer('RpQr');
            $table->integer('RpVaMandiri');
            $table->integer('RpVaBRI');
            $table->integer('RpVaBNI');
            $table->integer('RpDdBRI');
            $table->integer('RpLinkAja');
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
        Schema::dropIfExists('trans_chashbox');
    }
}
