<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * - locations.is_published: penjahit berstatus draf tidak tampil di publik.
     *   Data yang sudah ada dianggap terbit.
     * - deleted_at (soft delete): penjahit & ulasan yang dihapus masuk tempat sampah
     *   dan bisa dipulihkan sebelum dibersihkan permanen.
     */
    public function up(): void
    {
        Schema::table('locations', function (Blueprint $table) {
            $table->boolean('is_published')->default(true)->after('offers_home_visit')->index();
            $table->softDeletes();
        });

        Schema::table('reviews', function (Blueprint $table) {
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::table('locations', function (Blueprint $table) {
            $table->dropIndex(['is_published']);
            $table->dropColumn('is_published');
            $table->dropSoftDeletes();
        });

        Schema::table('reviews', function (Blueprint $table) {
            $table->dropSoftDeletes();
        });
    }
};
