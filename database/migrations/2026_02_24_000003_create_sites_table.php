<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateSitesTable extends Migration
{
    public function up()
    {
        Schema::create('sites', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('code')->unique()->nullable();
            $table->enum('type', ['warehouse', 'pop', 'hub', 'customer_site', 'office'])->default('warehouse');
            // Keterangan type:
            // warehouse     = Gudang Pusat / Gudang regional
            // pop           = POP (Point of Presence)
            // hub           = Hub/Distribution point
            // customer_site = Lokasi langsung di pelanggan (Villa/Hotel)
            // office        = Kantor internal
            $table->foreignId('region_id')->constrained('regions')->cascadeOnUpdate()->restrictOnDelete();
            $table->text('address')->nullable();
            $table->string('pic_name')->nullable();    // Person in Charge
            $table->string('pic_phone')->nullable();
            $table->text('notes')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('sites');
    }
}
