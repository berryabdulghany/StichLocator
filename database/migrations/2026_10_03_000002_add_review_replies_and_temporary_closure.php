<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Balasan penjahit atas ulasan pelanggan
        Schema::table('reviews', function (Blueprint $table) {
            $table->text('reply')->nullable()->after('tags');
            $table->timestamp('replied_at')->nullable()->after('reply');
        });

        // Libur sementara (tanpa mengubah jadwal mingguan)
        Schema::table('locations', function (Blueprint $table) {
            $table->date('closed_until')->nullable()->after('opening_hours');
            $table->string('closure_note', 120)->nullable()->after('closed_until');
        });
    }

    public function down(): void
    {
        Schema::table('reviews', function (Blueprint $table) {
            $table->dropColumn(['reply', 'replied_at']);
        });
        Schema::table('locations', function (Blueprint $table) {
            $table->dropColumn(['closed_until', 'closure_note']);
        });
    }
};
