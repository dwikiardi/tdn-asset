<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateTransactionDetailsTable extends Migration
{
    public function up()
    {
        Schema::create('transaction_details', function (Blueprint $table) {
            $table->id();
            $table->foreignId('transaction_id')->constrained()->cascadeOnDelete()->cascadeOnUpdate();
            // Link ke unit fisik (bukan tipe barang)
            $table->unsignedBigInteger('asset_unit_id');
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->unique(['transaction_id', 'asset_unit_id']); // satu unit tidak bisa 2x di transaksi yg sama
        });
    }

    public function down()
    {
        Schema::dropIfExists('transaction_details');
    }
}
