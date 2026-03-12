<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateAssetUnitLogsTable extends Migration
{
    public function up()
    {
        Schema::create('asset_unit_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('asset_unit_id')->constrained('asset_units')->cascadeOnDelete()->cascadeOnUpdate();

            // =============================================
            // AKSI — Audit trail
            // received     = Diterima masuk gudang
            // moved        = Dipindah antar site/gudang
            // deployed     = Dipasang ke pelanggan
            // retrieved    = Dicabut dari pelanggan
            // faulty_noted = Dilaporkan rusak
            // sent_rma     = Dikirim ke vendor (RMA)
            // rma_returned = Kembali dari vendor (RMA)
            // pulled       = Ditarik/dinonaktifkan
            // checked      = Pengecekan kondisi
            // =============================================
            $table->enum('action', [
                'received', 'moved', 'deployed', 'retrieved',
                'faulty_noted', 'sent_rma', 'rma_returned', 'pulled', 'checked'
            ]);

            // Status sebelum & sesudah aksi
            $table->string('from_status')->nullable();
            $table->string('to_status')->nullable();

            // Perpindahan lokasi
            $table->foreignId('from_site_id')->nullable()->constrained('sites')->nullOnDelete();
            $table->foreignId('to_site_id')->nullable()->constrained('sites')->nullOnDelete();

            // Pelanggan yang terlibat
            $table->foreignId('customer_id')->nullable()->constrained('customers')->nullOnDelete();

            // =============================================
            // REFERENSI KE STAFF TRIDATU NETMON (TANPA DATABASE DOUBLE)
            // Tidak ada FK ke local users — data staff diambil via API
            // =============================================
            $table->string('tridatu_user_id')->nullable();   // ID user dari Tridatu Netmon
            $table->string('tridatu_user_name')->nullable(); // Cache nama (untuk display offline)

            // User lokal yang mencatat (admin sistem)
            $table->foreignId('performed_by')->nullable()->constrained('users')->nullOnDelete();

            // Link ke transaksi jika ada
            $table->foreignId('transaction_id')->nullable();

            $table->text('notes')->nullable();
            $table->timestamps();

            $table->index('asset_unit_id');
            $table->index('tridatu_user_id');
            $table->index('action');
        });
    }

    public function down()
    {
        Schema::dropIfExists('asset_unit_logs');
    }
}
