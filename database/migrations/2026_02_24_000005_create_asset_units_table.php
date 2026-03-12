<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateAssetUnitsTable extends Migration
{
    public function up()
    {
        Schema::create('asset_units', function (Blueprint $table) {
            $table->id();
            $table->foreignId('asset_type_id')->constrained('asset_types')->cascadeOnDelete()->cascadeOnUpdate();

            // =============================================
            // IDENTITAS UNIK PERANGKAT
            // =============================================
            $table->string('serial_number')->unique();
            $table->string('mac_address')->unique()->nullable();
            $table->string('mac_address_2')->unique()->nullable(); // MAC ke-2 (contoh: wifi+eth)

            // =============================================
            // STATUS MANAJEMEN UNIT
            // new       = Baru, belum dicek/masuk gudang
            // in_stock  = Tersedia di gudang/site
            // deployed  = Terpasang aktif di pelanggan
            // faulty    = Rusak, dalam penanganan
            // rma       = Dikirim ke vendor untuk klaim garansi
            // pulled    = Bekas tarikan, sudah tidak aktif
            // =============================================
            $table->enum('status', ['new', 'in_stock', 'deployed', 'faulty', 'rma', 'pulled'])->default('new');

            // Lokasi fisik saat ini
            $table->foreignId('site_id')->nullable()->constrained('sites')->nullOnDelete();

            // Pelanggan yang menggunakan (jika deployed)
            $table->foreignId('customer_id')->nullable()->constrained('customers')->nullOnDelete();

            // Informasi pembelian unit ini (bisa override dari asset_type)
            $table->date('purchase_date')->nullable();
            $table->bigInteger('purchase_price')->nullable();
            $table->date('warranty_expires_at')->nullable();

            // Catatan kondisi / kerusakan
            $table->text('condition_notes')->nullable();

            // Kapan terakhir perangkat aktif/terdeteksi
            $table->timestamp('last_seen_at')->nullable();

            $table->timestamps();

            // Index untuk query cepat
            $table->index('status');
            $table->index('site_id');
            $table->index('customer_id');
        });
    }

    public function down()
    {
        Schema::dropIfExists('asset_units');
    }
}
