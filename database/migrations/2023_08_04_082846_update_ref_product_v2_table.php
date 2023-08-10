<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class UpdateRefProductV2Table extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('ref_product', function (Blueprint $table) {
            $table->boolean('is_composit')->default(0)->comment('product komposit');
            $table->string('type')->default('tunggal');
            $table->unsignedInteger('price_capital')->default(0);
            $table->unsignedInteger('price_min')->default(0);
            $table->unsignedInteger('price_max')->default(0);
            $table->unsignedInteger('stock_min')->default(0);
            $table->unsignedInteger('satuan_id')->nullable();
            $table->boolean('is_notification')->default(0);
        });

         //M to M product to raw product
         Schema::create('trans_product_raw', function (Blueprint $table) {
            $table->unsignedInteger('parent_id');
            $table->unsignedInteger('child_id');
            $table->unsignedInteger('qty');
        });

        //M to M product to raw product
        Schema::table('trans_stock', function (Blueprint $table) {
            $table->unsignedInteger('tenant_id');
            $table->unsignedInteger('satuan_id');
            $table->unsignedInteger('price_capital');
        });

       

    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('trans_stock', function (Blueprint $table) {
            $table->dropColumn(['tenant_id','satuan_id','price_capital']);
        });
        Schema::dropIfExists('trans_product_raw');
        Schema::table('ref_product', function (Blueprint $table) {
            $table->dropColumn(['is_composit','price_capital','price_min','price_max','stock_min','satuan_id','is_notification']);
        });
    }
}
