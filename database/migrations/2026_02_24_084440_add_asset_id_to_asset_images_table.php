<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddAssetIdToAssetImagesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        \Illuminate\Support\Facades\DB::statement('ALTER TABLE asset_images ADD asset_id BIGINT UNSIGNED NULL AFTER asset_type_id');
        \Illuminate\Support\Facades\DB::statement('UPDATE asset_images SET asset_id = asset_type_id');
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('asset_images', function (Blueprint $table) {
            //
        });
    }
}
