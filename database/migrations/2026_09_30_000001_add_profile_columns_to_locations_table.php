<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

return new class extends Migration
{
    /**
     * Kolom profil penjahit: slug untuk URL yang bisa dibagikan, deskripsi,
     * dan penanda layanan "panggilan ukur" (penjahit datang ke rumah).
     */
    public function up(): void
    {
        Schema::table('locations', function (Blueprint $table) {
            $table->string('slug')->nullable()->unique()->after('name');
            $table->text('description')->nullable()->after('address');
            $table->boolean('offers_home_visit')->default(false)->after('telepon');
        });

        // Isi slug untuk data yang sudah ada
        foreach (DB::table('locations')->orderBy('id')->get(['id', 'name']) as $location) {
            $base = Str::slug($location->name) ?: 'penjahit';
            $slug = $base;
            $i = 2;
            while (DB::table('locations')->where('slug', $slug)->exists()) {
                $slug = $base . '-' . $i++;
            }
            DB::table('locations')->where('id', $location->id)->update(['slug' => $slug]);
        }
    }

    public function down(): void
    {
        Schema::table('locations', function (Blueprint $table) {
            $table->dropUnique(['slug']);
            $table->dropColumn(['slug', 'description', 'offers_home_visit']);
        });
    }
};
