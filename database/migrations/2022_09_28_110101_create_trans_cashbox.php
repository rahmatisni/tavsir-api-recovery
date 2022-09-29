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
        Schema::create('trans_chashbox', function (Blueprint $table) {
            $table->increments('id');
            $table->int('cashbox');
            $table->int('cashbox_old');
            $table->int('perioda');
            $table->date('tgl_ubah_cashbox');
            $table->date('tgl_input_cashbox');
            $table->int('id_ops');
            $table->string('casheer_id');
            $table->int('tenant_id');
            $table->int('selisih_cashbox');
            $table->int('uang_pengeluaran');
            $table->text('ket_pengeluaran');
            $table->int('RpQr');
            $table->int('RpVaMandiri');
            $table->int('RpVaBRI');
            $table->int('RpVaBNI');
            $table->int('RpDdBRI');
            $table->int('RpLinkAja');
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
        Schema::dropIfExists('trans_chashbox');
    }
}
