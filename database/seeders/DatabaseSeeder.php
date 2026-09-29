<?php

namespace Database\Seeders;

use App\Enums\ReviewTag;
use App\Enums\ServiceCategory;
use App\Models\Admin;
use App\Models\Location;
use App\Models\Review;
use App\Models\User;
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
     *
     * Nomor telepon memakai awalan 0800 (nomor bebas pulsa, bukan nomor HP)
     * agar tombol WhatsApp di data demo tidak mengarah ke orang sungguhan.
     *
     * Foto dari Wikimedia Commons, lisensi & kredit ada di public/images/penjahit/credits.json.
     */
    public function run(): void
    {
        $demoUser = User::create([
            'name' => 'Pengguna Demo',
            'email' => 'user@stichlocator.test',
            'password' => Hash::make('password123'),
        ]);

        Admin::create([
            'name' => 'Admin Demo',
            'email' => 'admin@stichlocator.test',
            'password' => 'password123', // di-hash oleh cast 'hashed' pada model Admin
        ]);

        $reviewers = collect([
            'Rina Wulandari', 'Dimas Pratama', 'Siti Nurhaliza', 'Agus Setiawan',
            'Maya Anggraini', 'Fajar Nugroho', 'Lestari Putri', 'Bayu Saputra',
        ])->map(fn ($name, $i) => User::create([
            'name' => $name,
            'email' => 'reviewer' . ($i + 1) . '@stichlocator.test',
            'password' => Hash::make('password123'),
        ]))->prepend($demoUser);

        $credits = collect(json_decode(file_get_contents(public_path('images/penjahit/credits.json')), true))
            ->keyBy('file');

        foreach ($this->tailors() as $i => $data) {
            $location = Location::create([
                'name' => $data['name'],
                'address' => $data['address'],
                'description' => $data['description'],
                'telepon' => sprintf('0800%08d', $i + 1),
                'offers_home_visit' => $data['home_visit'] ?? false,
                'lat' => $data['lat'],
                'lng' => $data['lng'],
                'image_url' => 'images/penjahit/' . $data['photos'][0],
                'opening_hours' => $data['hours']['summary'],
                'status' => null,
                'rating' => null,
                'review_count' => 0,
            ]);

            foreach ($data['services'] as $order => [$category, $name, $price, $minDays, $maxDays]) {
                $location->services()->create([
                    'category' => $category,
                    'name' => $name,
                    'price_from' => $price,
                    'duration_min_days' => $minDays,
                    'duration_max_days' => $maxDays,
                    'sort_order' => $order,
                ]);
            }

            foreach ($data['hours']['week'] as $day => $range) {
                $location->hours()->create([
                    'day_of_week' => $day,
                    'opens_at' => $range[0] ?? null,
                    'closes_at' => $range[1] ?? null,
                ]);
            }

            foreach ($data['photos'] as $order => $file) {
                $credit = $credits[$file] ?? null;
                $location->photos()->create([
                    'path' => 'images/penjahit/' . $file,
                    'credit' => $credit ? "{$credit['author']} / {$credit['license']}" : null,
                    'source_url' => $credit['source'] ?? null,
                    'sort_order' => $order,
                ]);
            }

            foreach ($data['reviews'] as $r => [$rating, $text, $tags]) {
                Review::forceCreate([
                    'user_id' => $reviewers[($i + $r) % $reviewers->count()]->id,
                    'location_id' => $location->id,
                    'rating' => $rating,
                    'review' => $text,
                    'tags' => array_map(fn (ReviewTag $tag) => $tag->value, $tags),
                    'created_at' => now()->subDays($r * 3 + $i),
                    'updated_at' => now()->subDays($r * 3 + $i),
                ]);
            }

            $location->refreshRatingStats();
        }
    }

    /*
    |--------------------------------------------------------------------------
    | Pola jam operasional (0 = Minggu ... 6 = Sabtu, null = libur)
    |--------------------------------------------------------------------------
    */

    private function hours(string $pattern): array
    {
        return match ($pattern) {
            // Senin–Sabtu 08.00–17.00, Minggu libur
            'kantor' => ['summary' => '08:00 - 17:00', 'week' => [null, ...array_fill(0, 6, ['08:00', '17:00'])]],
            // Setiap hari 10.00–21.00 (di dalam mal/ruko ramai)
            'mal' => ['summary' => '10:00 - 21:00', 'week' => array_fill(0, 7, ['10:00', '21:00'])],
            // Pasar: Senin–Sabtu 07.00–16.00, Minggu 07.00–12.00
            'pasar' => ['summary' => '07:00 - 16:00', 'week' => [['07:00', '12:00'], ...array_fill(0, 6, ['07:00', '16:00'])]],
            // Permak malam: setiap hari 10.00–22.00, Jumat mulai 13.00
            'malam' => ['summary' => '10:00 - 22:00', 'week' => [
                ['10:00', '22:00'], ['10:00', '22:00'], ['10:00', '22:00'], ['10:00', '22:00'],
                ['10:00', '22:00'], ['13:00', '22:00'], ['10:00', '22:00'],
            ]],
            // Butik: Selasa–Minggu 09.00–18.00, Senin libur
            'butik' => ['summary' => '09:00 - 18:00', 'week' => [['09:00', '18:00'], null, ...array_fill(0, 5, ['09:00', '18:00'])]],
        };
    }

    /*
    |--------------------------------------------------------------------------
    | Data penjahit demo (Kota Bandung)
    |--------------------------------------------------------------------------
    | Alamat berformat "Jalan, Kecamatan, Kota Bandung"; kecamatan dipakai untuk filter Wilayah.
    |--------------------------------------------------------------------------
    | services: [kategori, nama layanan, harga mulai (Rp), min hari, maks hari]
    | reviews : [rating, teks, [tag...]]
    */

    private function tailors(): array
    {
        $K = ServiceCategory::Kebaya->value;
        $P = ServiceCategory::Permak->value;
        $S = ServiceCategory::Seragam->value;
        $J = ServiceCategory::Jas->value;
        $G = ServiceCategory::Gaun->value;

        $rapi = ReviewTag::Rapi;
        $waktu = ReviewTag::TepatWaktu;
        $ramah = ReviewTag::Ramah;
        $harga = ReviewTag::HargaPas;
        $sesuai = ReviewTag::SesuaiPesanan;

        return [
            [
                'name' => 'Penjahit Pak Budi',
                'address' => 'Jl. Braga No. 12, Sumur Bandung, Kota Bandung',
                'description' => 'Penjahit langganan pegawai kantoran di kawasan Braga sejak 1998. Spesialis kemeja, celana bahan, dan seragam kantor.',
                'lat' => -6.91750, 'lng' => 107.60940,
                'hours' => $this->hours('kantor'),
                'photos' => ['penjahit-tradisional.jpg', 'mengukur-kain.jpg', 'meteran-jahit.jpg'],
                'services' => [
                    [$S, 'Seragam kantor (kemeja)', 125000, 5, 7],
                    [$S, 'Celana bahan', 110000, 4, 6],
                    [$P, 'Potong & kecilkan celana', 25000, 1, 1],
                ],
                'reviews' => [
                    [5, 'Jahitan kemeja rapi sekali, ukurannya pas di badan.', [$rapi, $sesuai]],
                    [5, 'Sudah langganan bertahun-tahun, selalu tepat janji.', [$waktu, $ramah]],
                    [4, 'Hasil bagus, antrean agak panjang menjelang Lebaran.', [$rapi]],
                ],
            ],
            [
                'name' => 'Tailor Kebaya Bu Sri',
                'address' => 'Jl. Buah Batu No. 45, Lengkong, Kota Bandung',
                'description' => 'Kebaya modern dan brokat untuk wisuda, lamaran, dan pernikahan. Bisa pakai kain sendiri.',
                'lat' => -6.93520, 'lng' => 107.62080,
                'hours' => $this->hours('mal'),
                'photos' => ['manekin-kebaya.jpg', 'kebaya-peragaan.jpg', 'kebaya-bali.jpg', 'menjahit-motif.jpg'],
                'services' => [
                    [$K, 'Kebaya modern', 150000, 5, 7],
                    [$K, 'Kebaya brokat + furing', 250000, 7, 10],
                    [$G, 'Gaun pesta sederhana', 300000, 7, 14],
                    [$P, 'Permak kebaya / vermak', 30000, 1, 2],
                ],
                'reviews' => [
                    [5, 'Kebaya wisuda saya jadi cantik banget, detail payetnya rapi.', [$rapi, $sesuai]],
                    [4, 'Hasil bagus, tapi harus fitting dua kali.', [$rapi, $ramah]],
                    [5, 'Bu Sri sabar dengerin maunya kita. Recommended!', [$ramah, $sesuai]],
                    [4, 'Harga sesuai kualitas, selesai sehari lebih cepat.', [$harga, $waktu]],
                ],
            ],
            [
                'name' => 'Jahit Kilat Pasar Baru',
                'address' => 'Pasar Baru Trade Center Lt. 3, Jl. Otto Iskandardinata, Sumur Bandung, Kota Bandung',
                'description' => 'Jahit cepat di pusat kain Pasar Baru. Beli kain di bawah, jahit di sini, bisa ditunggu.',
                'lat' => -6.91780, 'lng' => 107.60460,
                'hours' => $this->hours('pasar'),
                'photos' => ['penjahit-pasar-kebumen.jpg', 'penjahit-pasar.jpg', 'kios-penjahit.jpg'],
                'services' => [
                    [$P, 'Permak celana / rok', 15000, 1, 1],
                    [$P, 'Ganti resleting', 20000, 1, 1],
                    [$G, 'Gamis & tunik', 120000, 3, 5],
                ],
                'reviews' => [
                    [5, 'Permak celana ditunggu 20 menit langsung jadi.', [$waktu, $harga]],
                    [4, 'Murah dan cepat, cocok buat yang buru-buru.', [$harga]],
                    [5, 'Gamis pesanan pas dan rapi.', [$rapi, $sesuai]],
                ],
            ],
            [
                'name' => 'Permak Jeans Mas Andi',
                'address' => 'Jl. Cihampelas No. 88, Coblong, Kota Bandung',
                'description' => 'Spesialis vermak jeans di "jalan jeans" Cihampelas: potong, kecilkan, ganti model, sampai tambal robek.',
                'lat' => -6.89450, 'lng' => 107.60450,
                'hours' => $this->hours('malam'),
                'photos' => ['vermak-levis-1.jpg', 'vermak-levis-2.jpg', 'mesin-obras.jpg'],
                'services' => [
                    [$P, 'Potong jeans (jahit asli)', 25000, 1, 1],
                    [$P, 'Kecilkan pinggang / paha', 35000, 1, 2],
                    [$P, 'Ubah model skinny / cutbray', 50000, 2, 3],
                ],
                'reviews' => [
                    [5, 'Potong jeans pakai jahitan asli, nggak kelihatan bekas permak.', [$rapi, $sesuai]],
                    [4, 'Buka sampai malam, pas buat pulang kerja.', [$waktu, $ramah]],
                    [4, 'Harga oke, hasil lumayan rapi.', [$harga]],
                    [5, 'Jeans kebesaran jadi pas banget.', [$sesuai, $rapi]],
                ],
            ],
            [
                'name' => 'Butik Jahit Melati',
                'address' => 'Jl. Ir. H. Juanda (Dago) No. 21, Coblong, Kota Bandung',
                'description' => 'Butik jahit di Dago untuk gaun pesta, bridesmaid, dan busana muslim dengan desain custom.',
                'lat' => -6.88560, 'lng' => 107.61350,
                'hours' => $this->hours('butik'),
                'photos' => ['penjahit-gaun.jpg', 'penjahit-studio.jpg', 'mesin-jahit-singer.jpg'],
                'services' => [
                    [$G, 'Gaun bridesmaid', 450000, 10, 14],
                    [$G, 'Gaun pesta custom', 750000, 14, 21],
                    [$K, 'Kebaya akad', 900000, 14, 21],
                ],
                'reviews' => [
                    [5, 'Gaun bridesmaid satu tim seragam dan jatuhnya bagus.', [$rapi, $sesuai]],
                    [5, 'Desainernya bantu pilih bahan, hasil melebihi ekspektasi.', [$ramah, $sesuai]],
                ],
            ],
            [
                'name' => 'Penjahit Seragam Jaya',
                'address' => 'Jl. Jend. Ahmad Yani No. 300, Cibeunying Kidul, Kota Bandung',
                'description' => 'Seragam sekolah, kantor, dan komunitas. Menerima pesanan satuan maupun partai.',
                'lat' => -6.91100, 'lng' => 107.63950,
                'hours' => $this->hours('kantor'),
                'photos' => ['penjahit-sukoharjo.jpg', 'workshop-jahit.jpg'],
                'services' => [
                    [$S, 'Seragam sekolah (atasan)', 85000, 3, 5],
                    [$S, 'Seragam komunitas / PDH', 175000, 7, 10],
                    [$P, 'Bordir nama', 15000, 1, 1],
                ],
                'reviews' => [
                    [5, 'Pesan 40 seragam komunitas, semua selesai sesuai jadwal.', [$waktu, $sesuai]],
                    [4, 'Bahan standar, jahitan kuat.', [$rapi, $harga]],
                ],
            ],
            [
                'name' => 'Rumah Kebaya Ayu',
                'address' => 'Jl. Dr. Setiabudi No. 17, Sukasari, Kota Bandung',
                'description' => 'Kebaya kutu baru dan kartini dengan sentuhan modern. Bisa panggilan ukur ke rumah.',
                'lat' => -6.87000, 'lng' => 107.59600,
                'home_visit' => true,
                'hours' => $this->hours('butik'),
                'photos' => ['kebaya-peragaan.jpg', 'manekin-kebaya.jpg', 'kebaya-bali.jpg'],
                'services' => [
                    [$K, 'Kebaya kutu baru', 175000, 5, 7],
                    [$K, 'Kebaya kartini', 220000, 7, 10],
                    [$K, 'Set kebaya keluarga (min. 4)', 600000, 14, 21],
                ],
                'reviews' => [
                    [5, 'Diukur langsung di rumah, praktis banget buat ibu saya.', [$ramah, $sesuai]],
                    [5, 'Kebaya kutu baru-nya cantik dan nyaman dipakai.', [$rapi]],
                    [4, 'Bagus, harga sedikit di atas rata-rata.', [$rapi, $waktu]],
                ],
            ],
            [
                'name' => 'Tailor Jas Pak Hendra',
                'address' => 'Jl. L.L.R.E. Martadinata (Riau) No. 9, Bandung Wetan, Kota Bandung',
                'description' => 'Tailor jas dan beskap di kawasan Riau. Pola dibuat per badan untuk hasil yang presisi.',
                'lat' => -6.90400, 'lng' => 107.61800,
                'hours' => $this->hours('mal'),
                'photos' => ['manekin-jas.jpg', 'penjahit-pria.jpg', 'meteran-kain.jpg'],
                'services' => [
                    [$J, 'Jas formal (1 set)', 1200000, 14, 21],
                    [$J, 'Beskap pengantin', 950000, 14, 21],
                    [$J, 'Vest / rompi', 350000, 7, 10],
                    [$P, 'Permak jas', 75000, 2, 3],
                ],
                'reviews' => [
                    [5, 'Jas pernikahan saya pas sempurna, bahunya jatuh bagus.', [$rapi, $sesuai]],
                    [5, 'Pak Hendra teliti banget waktu ukur.', [$ramah, $rapi]],
                    [4, 'Kualitas mantap, proses agak lama tapi sepadan.', [$rapi]],
                ],
            ],
            [
                'name' => 'Vermak Kilat Bang Udin',
                'address' => 'Jl. Kiaracondong No. 5, Kiaracondong, Kota Bandung',
                'description' => 'Vermak kilat dari gerobak legendaris dekat Stasiun Kiaracondong. Bisa ditunggu.',
                'lat' => -6.92550, 'lng' => 107.64450,
                'hours' => $this->hours('malam'),
                'photos' => ['vermak-levis-2.jpg', 'vermak-levis-1.jpg', 'penjahit-jalanan.jpg'],
                'services' => [
                    [$P, 'Potong celana', 15000, 1, 1],
                    [$P, 'Tambal / tisik robek', 20000, 1, 1],
                    [$P, 'Ganti karet pinggang', 20000, 1, 1],
                ],
                'reviews' => [
                    [4, 'Murah meriah, ditunggu sambil ngopi.', [$harga, $waktu]],
                    [4, 'Bang Udin ramah, hasil oke untuk harga segini.', [$ramah, $harga]],
                ],
            ],
            [
                'name' => 'Butik Gaun Anggun',
                'address' => 'Jl. Sukajadi No. 21, Sukajadi, Kota Bandung',
                'description' => 'Gaun pesta, prom, dan gaun anak dengan pilihan bahan premium.',
                'lat' => -6.89050, 'lng' => 107.59350,
                'hours' => $this->hours('mal'),
                'photos' => ['penjahit-studio.jpg', 'penjahit-gaun.jpg', 'menjahit-motif.jpg'],
                'services' => [
                    [$G, 'Gaun anak', 250000, 5, 7],
                    [$G, 'Gaun prom', 650000, 10, 14],
                    [$G, 'Gaun malam custom', 1100000, 14, 30],
                ],
                'reviews' => [
                    [5, 'Gaun prom anak saya jadi pusat perhatian!', [$sesuai, $rapi]],
                    [4, 'Bahan bagus, fitting nyaman.', [$rapi, $ramah]],
                    [3, 'Hasil bagus tapi mundur 3 hari dari janji.', [$rapi]],
                ],
            ],
            [
                'name' => 'Konveksi Seragam Maju',
                'address' => 'Jl. PHH Mustofa (Suci) No. 22, Cibeunying Kidul, Kota Bandung',
                'description' => 'Konveksi di sentra kaos Suci. Seragam sekolah dan kantor, harga grosir untuk pesanan partai.',
                'lat' => -6.90400, 'lng' => 107.63550,
                'hours' => $this->hours('kantor'),
                'photos' => ['kios-penjahit.jpg', 'mengukur-kain.jpg', 'penjahit-pasar.jpg'],
                'services' => [
                    [$S, 'Seragam SD/SMP/SMA', 75000, 3, 5],
                    [$S, 'Kemeja kantor partai (min. 12)', 95000, 7, 14],
                    [$S, 'Jaket almamater', 185000, 10, 14],
                ],
                'reviews' => [
                    [4, 'Harga grosir beneran murah untuk kualitasnya.', [$harga]],
                    [5, 'Jaket almamater angkatan kami rapi dan tepat waktu.', [$waktu, $rapi]],
                    [4, 'Pelayanan cepat tanggap lewat WA.', [$ramah]],
                ],
            ],
            [
                'name' => 'Penjahit Panggilan Pak Darto',
                'address' => 'Jl. Terusan Jakarta No. 40, Antapani, Kota Bandung',
                'description' => 'Penjahit keliling: datang ke rumah untuk ukur, ambil, dan antar jahitan.',
                'lat' => -6.91350, 'lng' => 107.65850,
                'home_visit' => true,
                'hours' => $this->hours('kantor'),
                'photos' => ['vermak-keliling.jpg', 'penjahit-rumahan.jpg', 'meteran-jahit.jpg'],
                'services' => [
                    [$P, 'Permak panggilan (min. 3 potong)', 30000, 1, 3],
                    [$S, 'Kemeja custom', 150000, 5, 7],
                    [$J, 'Celana & rompi', 250000, 7, 10],
                ],
                'reviews' => [
                    [5, 'Nggak perlu keluar rumah, Pak Darto datang ukur langsung.', [$ramah, $sesuai]],
                    [5, 'Antar jemput jahitan tepat waktu.', [$waktu]],
                ],
            ],
        ];
    }
}
