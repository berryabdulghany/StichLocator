<?php

namespace Database\Seeders;

use App\Models\Admin;
use App\Models\Location;
use App\Models\Review;
use App\Models\User;
// use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     *
     * Akun untuk development lokal:
     *   User  : user@stichlocator.test  / password123
     *   Admin : admin@stichlocator.test / password123
     */
    public function run(): void
    {
        $user = User::create([
            'name' => 'Pengguna Demo',
            'email' => 'user@stichlocator.test',
            'password' => Hash::make('password123'),
        ]);

        Admin::create([
            'name' => 'Admin Demo',
            'email' => 'admin@stichlocator.test',
            'password' => 'password123', // di-hash oleh cast 'hashed' pada model Admin
        ]);

        $penjahit = [
            ['Penjahit Pak Budi', 'Jl. Sabang No. 12, Menteng, Jakarta Pusat', '081234567801', -6.18520, 106.82640, '08:00 - 17:00'],
            ['Tailor Kebaya Bu Sri', 'Jl. Kramat Raya No. 45, Senen, Jakarta Pusat', '081234567802', -6.18950, 106.84410, '09:00 - 20:00'],
            ['Jahit Kilat Tanah Abang', 'Pasar Tanah Abang Blok A, Jakarta Pusat', '081234567803', -6.18610, 106.81330, '07:00 - 16:00'],
            ['Permak Jeans Mas Andi', 'Jl. Tebet Raya No. 8, Tebet, Jakarta Selatan', '081234567804', -6.22640, 106.85460, '10:00 - 22:00'],
            ['Butik Jahit Melati', 'Jl. Kemang Raya No. 21, Jakarta Selatan', '081234567805', -6.26060, 106.81390, '09:00 - 18:00'],
            ['Penjahit Seragam Jaya', 'Jl. Salemba Raya No. 30, Jakarta Pusat', '081234567806', -6.19590, 106.85120, '08:00 - 16:00'],
        ];

        $ulasan = [
            [5, 'Hasil jahitan rapi dan tepat waktu. Sangat puas!'],
            [4, 'Bagus, harga terjangkau. Antrian agak lama.'],
            [5, 'Pelayanan ramah, ukuran pas sekali.'],
            [3, 'Lumayan, tapi ada sedikit revisi.'],
        ];

        foreach ($penjahit as $i => [$name, $address, $telepon, $lat, $lng, $hours]) {
            $location = Location::create([
                'name' => $name,
                'address' => $address,
                'telepon' => $telepon,
                'lat' => $lat,
                'lng' => $lng,
                'opening_hours' => $hours,
                'image_url' => 'https://picsum.photos/seed/penjahit' . ($i + 1) . '/600/400',
                'status' => 'Buka',
                'rating' => null,
                'reviews' => 0,
            ]);

            foreach (array_slice($ulasan, 0, ($i % 4) + 1) as $d => [$rating, $text]) {
                Review::forceCreate([
                    'user_id' => $user->id,
                    'location_id' => $location->id,
                    'rating' => $rating,
                    'review' => $text,
                    'created_at' => now()->subDays($d * 2),
                ]);
            }

            $location->update([
                'rating' => round($location->reviews()->avg('rating'), 1),
                'reviews' => $location->reviews()->count(),
            ]);
        }
    }
}
