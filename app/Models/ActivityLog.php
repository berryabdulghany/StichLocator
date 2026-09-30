<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ActivityLog extends Model
{
    public const UPDATED_AT = null;

    protected $fillable = ['admin_id', 'tailor_account_id', 'action', 'subject_type', 'subject_id', 'description', 'properties'];

    protected $casts = [
        'properties' => 'array',
        'created_at' => 'datetime',
    ];

    public function admin()
    {
        return $this->belongsTo(Admin::class);
    }

    /** Mitra penjahit yang melakukan tindakan (jika bukan admin) */
    public function tailorAccount()
    {
        return $this->belongsTo(TailorAccount::class);
    }

    /** Kalimat log dalam bahasa aktif, misalnya "Mengubah penjahit Tailor Kebaya Bu Sri" */
    public function message(): string
    {
        return __($this->description, $this->properties ?? []);
    }
}
