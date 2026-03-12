<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Previously 'assets' table.
 * Now renamed to 'asset_types' — represents the type/model of a device.
 * Physical units are in 'asset_units' table.
 */
class CreateAssetsTable extends Migration
{
    public function up()
    {
        Schema::create('asset_types', function (Blueprint $table) {
            $table->id();
            $table->foreignId('category_id')->constrained()->cascadeOnDelete()->cascadeOnUpdate();
            $table->unsignedBigInteger('supplier_id')->nullable(); // FK tanpa constraint (suppliers migrasi belakangan)
            $table->string('name');                          // Nama tipe: "Router AX1800"
            $table->string('brand')->nullable();             // Merek: Mikrotik, Ubiquiti
            $table->string('model')->nullable();             // Nomor model resmi
            $table->string('uid')->unique()->nullable();     // Kode internal (opsional)
            $table->text('description')->nullable();
            $table->json('specifications')->nullable();      // Spesifikasi teknis (JSON)
            $table->year('production_year')->nullable();
            $table->bigInteger('purchase_price_default')->nullable();
            $table->integer('warranty_months')->nullable()->default(12);
            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('asset_types');
    }
}
