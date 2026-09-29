<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Kolom jumlah ulasan bernama "reviews" bentrok dengan relasi Location::reviews(),
     * sehingga $location->reviews mengembalikan angka, bukan daftar ulasan.
     */
    public function up(): void
    {
        Schema::table('locations', function (Blueprint $table) {
            $table->renameColumn('reviews', 'review_count');
        });
    }

    public function down(): void
    {
        Schema::table('locations', function (Blueprint $table) {
            $table->renameColumn('review_count', 'reviews');
        });
    }
};
