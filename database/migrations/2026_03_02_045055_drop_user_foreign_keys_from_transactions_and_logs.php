<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class DropUserForeignKeysFromTransactionsAndLogs extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('transactions', function (Blueprint $table) {
            $table->dropForeign(['created_by']);
        });

        Schema::table('asset_unit_logs', function (Blueprint $table) {
            $table->dropForeign(['performed_by']);
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('transactions', function (Blueprint $table) {
            $table->foreign('created_by')->references('id')->on('users')->nullOnDelete();
        });

        Schema::table('asset_unit_logs', function (Blueprint $table) {
            $table->foreign('performed_by')->references('id')->on('users')->nullOnDelete();
        });
    }
}
