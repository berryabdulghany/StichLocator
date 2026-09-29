<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Daftar layanan tiap penjahit ("nota jahit"): kategori, harga mulai, dan estimasi pengerjaan.
     */
    public function up(): void
    {
        Schema::create('location_services', function (Blueprint $table) {
            $table->id();
            $table->foreignId('location_id')->constrained()->cascadeOnDelete();
            $table->string('category', 20)->index();   // App\Enums\ServiceCategory
            $table->string('name');
            $table->unsignedInteger('price_from');     // Rupiah
            $table->unsignedTinyInteger('duration_min_days');
            $table->unsignedTinyInteger('duration_max_days');
            $table->unsignedSmallInteger('sort_order')->default(0);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('location_services');
    }
};
