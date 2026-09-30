<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Statistik harian per penjahit: kunjungan halaman detail, klik WhatsApp, dan klik rute.
 * Satu baris per penjahit per hari (angka ditambah, bukan satu baris per kunjungan).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('location_daily_stats', function (Blueprint $table) {
            $table->id();
            $table->foreignId('location_id')->constrained()->cascadeOnDelete();
            $table->date('date');
            $table->unsignedInteger('views')->default(0);
            $table->unsignedInteger('whatsapp_clicks')->default(0);
            $table->unsignedInteger('route_clicks')->default(0);
            $table->unique(['location_id', 'date']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('location_daily_stats');
    }
};
