<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class MigrateExistingAssetsToUnits extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        $assetTypes = \Illuminate\Support\Facades\DB::table('asset_types')->get();
        foreach ($assetTypes as $type) {
            // Cek apakah sudah ada unitnya
            $exists = \Illuminate\Support\Facades\DB::table('asset_units')
                ->where('asset_type_id', $type->id)
                ->exists();

            if (!$exists) {
                \Illuminate\Support\Facades\DB::table('asset_units')->insert([
                    'asset_type_id' => $type->id,
                    'serial_number' => $type->uid, // Gunakan UID lama sebagai serial number
                    'status'        => ($type->status == 0) ? 'in_stock' : 'deployed',
                    'purchase_date' => $type->purchase_date,
                    'purchase_price'=> $type->purchase_price,
                    'condition_notes'=> $type->condition,
                    'created_at'    => now(),
                    'updated_at'    => now(),
                ]);
            }
        }
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('asset_units', function (Blueprint $table) {
            //
        });
    }
}
