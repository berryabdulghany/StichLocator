<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Tag cepat ulasan (App\Enums\ReviewTag), disimpan sebagai array JSON.
     */
    public function up(): void
    {
        Schema::table('reviews', function (Blueprint $table) {
            $table->json('tags')->nullable()->after('review');
        });
    }

    public function down(): void
    {
        Schema::table('reviews', function (Blueprint $table) {
            $table->dropColumn('tags');
        });
    }
};
