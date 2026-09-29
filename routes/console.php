<?php

use App\Models\Location;
use App\Models\Review;
use Illuminate\Support\Facades\Schedule;

// Hapus permanen penjahit & ulasan yang sudah lebih dari 30 hari di tempat sampah.
// Di server, jalankan scheduler lewat cron: * * * * * php artisan schedule:run
Schedule::command('model:prune', ['--model' => [Location::class, Review::class]])->daily();
