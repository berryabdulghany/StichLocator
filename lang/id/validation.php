<?php

/*
|--------------------------------------------------------------------------
| Pesan validasi Bahasa Indonesia
|--------------------------------------------------------------------------
| Hanya aturan yang dipakai di aplikasi ini. Aturan lain otomatis
| memakai pesan bahasa Inggris bawaan Laravel (fallback locale).
*/

return [
    'boolean' => ':Attribute harus bernilai benar atau salah.',
    'gte' => [
        'numeric' => ':Attribute harus lebih besar atau sama dengan :value.',
    ],
    'regex' => 'Format :attribute tidak valid.',
    'required_if' => ':Attribute wajib diisi.',
    'size' => [
        'array' => ':Attribute harus berisi :size item.',
    ],
    'between' => [
        'numeric' => ':Attribute harus di antara :min dan :max.',
        'string' => ':Attribute harus di antara :min dan :max karakter.',
    ],
    'confirmed' => 'Konfirmasi :attribute tidak cocok.',
    'current_password' => 'Password saat ini salah.',
    'date_format' => ':Attribute harus berformat :format.',
    'different' => ':Attribute harus berbeda dengan :other.',
    'email' => ':Attribute harus berupa alamat email yang valid.',
    'exists' => ':Attribute yang dipilih tidak valid.',
    'image' => ':Attribute harus berupa gambar.',
    'integer' => ':Attribute harus berupa angka bulat.',
    'max' => [
        'file' => ':Attribute maksimal :max kilobyte.',
        'numeric' => ':Attribute maksimal :max.',
        'string' => ':Attribute maksimal :max karakter.',
    ],
    'mimes' => ':Attribute harus berupa file bertipe: :values.',
    'min' => [
        'file' => ':Attribute minimal :min kilobyte.',
        'numeric' => ':Attribute minimal :min.',
        'string' => ':Attribute minimal :min karakter.',
    ],
    'numeric' => ':Attribute harus berupa angka.',
    'required' => ':Attribute wajib diisi.',
    'string' => ':Attribute harus berupa teks.',
    'unique' => ':Attribute sudah digunakan.',
    'url' => ':Attribute harus berupa URL yang valid.',

    'attributes' => [
        'name' => 'nama',
        'email' => 'email',
        'password' => 'password',
        'current_password' => 'password saat ini',
        'profile_picture' => 'foto profil',
        'rating' => 'rating',
        'review' => 'ulasan',
        'location_id' => 'penjahit',
    ],
];
