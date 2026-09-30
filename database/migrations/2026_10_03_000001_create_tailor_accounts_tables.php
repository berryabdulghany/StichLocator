<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Akun mitra penjahit: satu akun untuk satu penjahit, dibuat lewat undangan dari admin.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('tailor_accounts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('location_id')->unique()->constrained()->cascadeOnDelete();
            $table->string('name');
            $table->string('email')->unique();
            $table->string('password');
            $table->rememberToken();
            $table->timestamp('last_login_at')->nullable();
            $table->timestamps();
        });

        // Link undangan / atur ulang password. Token disimpan sebagai hash SHA-256.
        Schema::create('tailor_invitations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('location_id')->constrained()->cascadeOnDelete();
            $table->string('email');
            $table->string('token', 64)->unique();
            $table->timestamp('expires_at');
            $table->timestamp('accepted_at')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('admins')->nullOnDelete();
            $table->timestamps();
        });

        // Log aktivitas juga mencatat tindakan mitra penjahit
        Schema::table('activity_logs', function (Blueprint $table) {
            $table->foreignId('tailor_account_id')->nullable()->after('admin_id')->constrained()->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('activity_logs', function (Blueprint $table) {
            $table->dropConstrainedForeignId('tailor_account_id');
        });
        Schema::dropIfExists('tailor_invitations');
        Schema::dropIfExists('tailor_accounts');
    }
};
