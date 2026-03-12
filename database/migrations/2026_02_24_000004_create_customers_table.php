<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateCustomersTable extends Migration
{
    public function up()
    {
        Schema::create('customers', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('phone')->nullable();
            $table->string('email')->nullable();
            $table->text('address')->nullable();
            $table->foreignId('site_id')->nullable()->constrained('sites')->nullOnDelete();

            // =============================================
            // INTEGRASI TRIDATU NETMON
            // =============================================
            // ID customer dari sistem Tridatu Netmon / ticketing external
            $table->string('external_id')->unique()->nullable();
            // Nama sistem sumber (contoh: "tridatu_netmon", "whmcs")
            $table->string('external_source')->nullable()->default('tridatu_netmon');
            // Metadata tambahan dari sistem luar (JSON)
            $table->json('external_metadata')->nullable();

            $table->boolean('is_active')->default(true);
            $table->text('notes')->nullable();
            $table->timestamp('synced_at')->nullable(); // kapan terakhir data di-sync dari external
            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('customers');
    }
}
