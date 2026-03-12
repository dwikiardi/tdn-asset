<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddCompatibilityColumnsToAssetTypesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('asset_types', function (Blueprint $table) {
            $table->text('specification')->nullable();
            $table->date('purchase_date')->nullable();
            $table->bigInteger('purchase_price')->nullable();
            $table->string('condition')->nullable();
            $table->integer('status')->default(0); // 0=standby, 1=not standby (old logic)
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('asset_types', function (Blueprint $table) {
            //
        });
    }
}
