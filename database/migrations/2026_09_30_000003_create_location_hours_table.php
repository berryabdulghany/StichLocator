<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Jam operasional per hari. day_of_week mengikuti Carbon: 0 = Minggu ... 6 = Sabtu.
     * opens_at/closes_at bernilai null berarti hari itu libur.
     */
    public function up(): void
    {
        Schema::create('location_hours', function (Blueprint $table) {
            $table->id();
            $table->foreignId('location_id')->constrained()->cascadeOnDelete();
            $table->unsignedTinyInteger('day_of_week');
            $table->string('opens_at', 5)->nullable();   // "08:00"
            $table->string('closes_at', 5)->nullable();  // "17:00"
            $table->timestamps();

            $table->unique(['location_id', 'day_of_week']);
        });

        // Salin jam buka lama ("08:00 - 17:00") ke semua hari untuk data yang sudah ada
        $now = now();
        foreach (DB::table('locations')->whereNotNull('opening_hours')->get(['id', 'opening_hours']) as $location) {
            if (! preg_match('/^\s*(\d{2}:\d{2})\s*-\s*(\d{2}:\d{2})\s*$/', $location->opening_hours, $m)) {
                continue;
            }
            $rows = [];
            foreach (range(0, 6) as $day) {
                $rows[] = [
                    'location_id' => $location->id,
                    'day_of_week' => $day,
                    'opens_at' => $m[1],
                    'closes_at' => $m[2],
                    'created_at' => $now,
                    'updated_at' => $now,
                ];
            }
            DB::table('location_hours')->insert($rows);
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('location_hours');
    }
};
