<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateRefSharingTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('ref_sharing', function (Blueprint $table) {
            $table->id();
            $table->string('nama_pks');
            $table->string('nomor_pks');
            $table->unsignedInteger('pengelola_id');
            $table->unsignedInteger('tenant_id');
            $table->unsignedInteger('persentase_pengelola');
            $table->unsignedInteger('persentase_supertenant');
            $table->unsignedInteger('persentase_tenant');
            $table->date('waktu_mulai');
            $table->date('waktu_selesai');
            $table->string('status');
            $table->string('file')->nullable();
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
        Schema::dropIfExists('ref_sharing');
    }
}
