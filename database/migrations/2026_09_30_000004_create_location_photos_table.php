<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Galeri foto penjahit. credit & source_url untuk atribusi lisensi foto (misalnya Creative Commons).
     */
    public function up(): void
    {
        Schema::create('location_photos', function (Blueprint $table) {
            $table->id();
            $table->foreignId('location_id')->constrained()->cascadeOnDelete();
            $table->string('path');                    // URL penuh atau path relatif di folder public
            $table->string('credit')->nullable();
            $table->string('source_url')->nullable();
            $table->unsignedSmallInteger('sort_order')->default(0);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('location_photos');
    }
};
