<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateAssetImagesTable extends Migration
{
    public function up()
    {
        Schema::create('asset_images', function (Blueprint $table) {
            $table->id();
            // Gambar direlasikan ke tipe aset, bukan unit fisik
            $table->foreignId('asset_type_id')->constrained('asset_types')->cascadeOnDelete()->cascadeOnUpdate();
            $table->string('name');         // nama file
            $table->string('path');         // path file di storage
            $table->boolean('is_primary')->default(false);
            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('asset_images');
    }
}
