<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateTransactionsTable extends Migration
{
    public function up()
    {
        Schema::create('transactions', function (Blueprint $table) {
            $table->id();
            $table->string('transaction_number')->unique();

            // Tipe transaksi
            // stock_in    = Barang masuk gudang (dari supplier/pembelian)
            // stock_out   = Barang keluar gudang ke site lain
            // transfer    = Perpindahan antar site/gudang
            // deployment  = Pemasangan ke pelanggan
            // retrieval   = Pencabutan dari pelanggan
            // rma_out     = Kirim ke vendor untuk RMA
            // rma_in      = Terima kembali dari vendor (RMA)
            $table->enum('type', ['stock_in', 'stock_out', 'transfer', 'deployment', 'retrieval', 'rma_out', 'rma_in']);

            // Lokasi asal & tujuan
            $table->unsignedBigInteger('from_site_id')->nullable();
            $table->unsignedBigInteger('to_site_id')->nullable();

            // Pelanggan tujuan (untuk deployment/retrieval)
            $table->unsignedBigInteger('customer_id')->nullable();

            // =============================================
            // STAFF DARI TRIDATU NETMON (TIDAK DISIMPAN LOKAL)
            // =============================================
            $table->string('tridatu_user_id')->nullable();    // ID teknisi dari Tridatu
            $table->string('tridatu_user_name')->nullable();  // Cache nama teknisi

            // User admin lokal yang membuat transaksi
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();

            $table->enum('status', ['pending', 'completed', 'cancelled'])->default('pending');
            $table->date('transaction_date')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->index('type');
            $table->index('status');
            $table->index('tridatu_user_id');
        });
    }

    public function down()
    {
        Schema::dropIfExists('transactions');
    }
}
