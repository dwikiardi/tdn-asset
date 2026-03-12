<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddOwnershipStatusToAssetUnitsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('asset_units', function (Blueprint $table) {
            $table->enum('ownership_status', ['company_owned', 'rented_to_customer', 'sold_to_customer'])->default('company_owned')->after('status');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('asset_units', function (Blueprint $table) {
            $table->dropColumn('ownership_status');
        });
    }
}
