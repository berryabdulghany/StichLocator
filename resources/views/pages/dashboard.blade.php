@extends('layouts.app')

@section('content')
    <div id="map" class="absolute inset-0 z-0" style="height: 100%; width: 100%;"></div>

    <!-- Detail Lokasi -->
    <div id="detailContent" class="absolute top-0 left-0 h-full w-full md:w-[400px] bg-white shadow-lg z-20 transform -translate-x-full">
        <div class="h-full flex flex-col">
            <!-- Tombol Close -->
            <button onclick="closeDetails()" class="absolute top-3 right-3 w-8 h-8 flex items-center justify-center bg-white text-black text-xl font-bold rounded-full hover:bg-gray-200 focus:outline-none z-50">
                &times;
            </button>

            <!-- Sticky Header (Initially Hidden) -->
            <div id="stickyDetailHeader" class="hidden p-4 border-b bg-white shadow-sm">
                <h2 id="stickyDetailName" class="text-lg font-bold truncate"></h2>
            </div>

            <!-- Konten Scrollable -->
            <div id="scrollableContent" class="flex-grow overflow-y-auto scrollbar-hide">
                <!-- Gambar Lokasi -->
                <img id="detailImage" src="" alt="Foto Lokasi" class="w-full h-48 object-cover">

                <div class="p-4">
                    <!-- Header -->
                    <div class="pb-3 border-b">
                        <h2 id="detailName" class="text-2xl font-bold">Nama Lokasi</h2>
                        <div class="flex items-center text-sm text-gray-600 mt-1">
                            <span id="detailRating" class="text-yellow-500 font-bold">0.0</span>
                            <div id="detailRatingStars" class="flex items-center ml-1 text-yellow-400"></div>
                            <span id="detailReviewCount" class="ml-2">(0 ulasan)</span>
                        </div>
                        <p id="detailDescription" class="text-sm text-gray-500 mt-1">Deskripsi Singkat Penjahit</p>
                        <p id="detailStatus" class="text-sm font-semibold mt-1">Status</p>
                    </div>

                    <!-- Tombol Aksi -->
                    <div class="grid grid-cols-3 gap-2 py-3 border-b text-center">
                        <a id="routeButton" href="#" target="_blank" class="flex flex-col items-center text-blue-600 hover:bg-gray-100 p-2 rounded-lg">
                            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 19l9 2-9-18-9 18 9-2zm0 0v-8"></path></svg>
                            <span class="text-xs mt-1">Rute</span>
                        </a>
                        <button class="flex flex-col items-center text-blue-600 hover:bg-gray-100 p-2 rounded-lg">
                            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 5a2 2 0 012-2h10a2 2 0 012 2v16l-7-3.5L5 21V5z"></path></svg>
                            <span class="text-xs mt-1">Simpan</span>
                        </button>
                        <button id="shareButton" class="flex flex-col items-center text-blue-600 hover:bg-gray-100 p-2 rounded-lg">
                            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8.684 13.342C8.886 12.938 9 12.482 9 12s-.114-.938-.316-1.342m0 2.684a3 3 0 110-2.684m0 2.684l6.632 3.316m-6.632-6l6.632-3.316m0 0a3 3 0 105.367-2.684 3 3 0 00-5.367 2.684zm0 9.368a3 3 0 105.367 2.684 3 3 0 00-5.367-2.684z"></path></svg>
                            <span class="text-xs mt-1">Bagikan</span>
                        </button>
                    </div>

                    <!-- Tab Navigation -->
                    <div class="flex border-b">
                        <button id="summaryTab" class="flex-1 py-2 text-center text-sm font-semibold text-blue-600 border-b-2 border-blue-600 focus:outline-none">Ringkasan</button>
                        <button id="reviewsTab" class="flex-1 py-2 text-center text-sm font-semibold text-gray-600 focus:outline-none">Ulasan</button>
                    </div>

                    <!-- Tab Content -->
                    <div id="summaryContent" class="py-3">
                        <!-- Informasi Detail -->
                        <div class="py-3 border-b space-y-3 text-sm">
                            <div class="flex items-start">
                                <svg class="w-5 h-5 text-gray-500 mr-3 mt-1" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"></path><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"></path></svg>
                                <span id="detailAddress">Alamat Lengkap</span>
                            </div>
                            <div class="flex items-center">
                                <svg class="w-5 h-5 text-gray-500 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z"></path></svg>
                                <span id="detailTelepon">Nomor Telepon</span>
                            </div>
                            <div class="flex items-center">
                                <svg class="w-5 h-5 text-gray-500 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.828 10.172a4 4 0 00-5.656 0l-4 4a4 4 0 105.656 5.656l1.102-1.101m-.758-4.899a4 4 0 005.656 0l4-4a4 4 0 00-5.656-5.656l-1.1 1.1"></path></svg>
                                <span id="detailSocialMedia" class="text-blue-600 hover:underline">Media Sosial</span>
                            </div>
                            <div class="flex items-center text-blue-600 hover:underline cursor-pointer">
                                <svg class="w-5 h-5 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                                <span id="detailOpeningHours">Tambahkan jam buka</span>
                            </div>
                        </div>
                        <!-- Ringkasan Ulasan -->
                        <div id="reviewSummary" class="py-3 border-b">
                            <h3 class="text-lg font-semibold mb-2">Ringkasan Ulasan</h3>
                            <!-- Konten di-generate oleh JS -->
                        </div>
                        <!-- Daftar Review -->
                        <div class="py-3">
                            <h3 class="text-lg font-semibold">Ulasan Pengguna</h3>
                            <ul id="reviewList" class="space-y-3 mt-2">
                                <!-- Konten di-generate oleh JS -->
                            </ul>
                        </div>
                    </div>

                    <div id="reviewsContent" class="py-3 hidden">
                        <!-- Formulir Input Review -->
                        <div class="py-3 border-b">
                            <h3 class="text-lg font-semibold">Berikan Review Anda</h3>
                            @auth
                                <form id="reviewForm" class="review-form mt-2">
                                    @csrf
                                    <input type="hidden" name="location_id" id="locationId">
                                    <select name="rating" id="rating" class="w-full p-2 border rounded-lg focus:ring focus:ring-blue-300">
                                        <option value="5">⭐️⭐️⭐️⭐️⭐️ - Sangat Baik</option>
                                        <option value="4">⭐️⭐️⭐️⭐️ - Baik</option>
                                        <option value="3">⭐️⭐️⭐️ - Cukup</option>
                                        <option value="2">⭐️ - Kurang</option>
                                        <option value="1">⭐️ - Buruk</option>
                                    </select>
                                    <textarea name="review" id="review" rows="3" class="w-full p-2 border rounded-lg focus:ring focus:ring-blue-300 mt-2" placeholder="Bagikan pengalaman Anda..." required></textarea>
                                    <button type="submit" class="mt-2 w-full bg-blue-600 text-white py-2 rounded-lg hover:bg-blue-700">Kirim Ulasan</button>
                                </form>
                            @else
                                <div class="mt-2 p-4 bg-blue-50 border border-blue-200 text-blue-800 rounded-lg text-center">
                                    <p class="mb-2">Anda perlu login untuk memberikan ulasan.</p>
                                    <a href="{{ route('login') }}" class="inline-block bg-blue-600 text-white py-2 px-4 rounded-lg hover:bg-blue-700">Login Sekarang</a>
                                </div>
                            @endauth
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection

@push('scripts')
    @include('scripts.map')

    <script>
        // Fungsi untuk membuka/menutup popup detail
        function closeDetails() {
            const detailContent = document.getElementById("detailContent");
            detailContent.classList.add("-translate-x-full");
            detailContent.style.zIndex = 20; // Langsung kembalikan z-index
        }

        // Variabel untuk menyimpan ID lokasi yang sedang aktif
        let currentLocationId = null;

        // Deklarasi variabel untuk elemen DOM, akan diinisialisasi di DOMContentLoaded
        let summaryTab, reviewsTab, summaryContent, reviewsContent;
        let scrollableContent, stickyHeader, detailImage;
        let reviewForm; // Added for review form

        document.addEventListener('DOMContentLoaded', () => {
            // Inisialisasi elemen setelah DOM siap
            summaryTab = document.getElementById('summaryTab');
            reviewsTab = document.getElementById('reviewsTab');
            summaryContent = document.getElementById('summaryContent');
            reviewsContent = document.getElementById('reviewsContent');
            reviewForm = document.getElementById('reviewForm'); // Initialize reviewForm here

            scrollableContent = document.getElementById('scrollableContent');
            stickyHeader = document.getElementById('stickyDetailHeader');
            detailImage = document.getElementById('detailImage');

            // Event listeners for tabs
            if (summaryTab && reviewsTab && summaryContent && reviewsContent) {
                summaryTab.addEventListener('click', () => {
                    summaryTab.classList.add('text-blue-600', 'border-blue-600');
                    summaryTab.classList.remove('text-gray-600');
                    reviewsTab.classList.add('text-gray-600');
                    reviewsTab.classList.remove('text-blue-600', 'border-blue-600');

                    summaryContent.classList.remove('hidden');
                    reviewsContent.classList.add('hidden');
                });

                reviewsTab.addEventListener('click', () => {
                    reviewsTab.classList.add('text-blue-600', 'border-blue-600');
                    reviewsTab.classList.remove('text-gray-600');
                    summaryTab.classList.add('text-gray-600');
                    summaryTab.classList.remove('text-blue-600', 'border-blue-600');

                    reviewsContent.classList.remove('hidden');
                    summaryContent.classList.add('hidden');
                });
            }

            // Event listener for sticky header
            if (scrollableContent && stickyHeader && detailImage) {
                scrollableContent.addEventListener('scroll', () => {
                    if (scrollableContent.scrollTop > detailImage.offsetHeight) {
                        stickyHeader.classList.remove('hidden');
                    } else {
                        stickyHeader.classList.add('hidden');
                    }
                });
            }

            // Handle form submission - only if reviewForm exists (user is logged in)
            if (reviewForm) {
                reviewForm.addEventListener('submit', function(e) {
                    e.preventDefault();
                    const formData = new FormData(this);
                    const locationId = document.getElementById('locationId').value;

                    fetch('{{ route("submit-review") }}', {
                        method: 'POST',
                        body: formData,
                        headers: { 'X-CSRF-TOKEN': document.querySelector('input[name="_token"]').value }
                    })
                    .then(response => {
                        if (!response.ok) {
                            return response.json().then(err => {
                                throw new Error(err.message || 'Gagal mengirim review. Status: ' + response.status);
                            }).catch(() => {
                                throw new Error('Gagal mengirim review. Status: ' + response.status);
                            });
                        }
                        return response.json();
                    })
                    .then(data => {
                        if (data.success) {
                            document.getElementById('review').value = '';
                            document.getElementById('rating').value = '5';
                            fetchReviews(locationId); // Refresh reviews list and summary
                        } else {
                            alert('Error: ' + (data.message || 'Gagal mengirim review.'));
                        }
                    })
                    .catch(error => {
                        console.error('Error submitting review:', error);
                        alert('Terjadi kesalahan saat mengirim ulasan: ' + error.message);
                    });
                });
            }
        });

        function showDetails(id, name, address, telepon, rating, reviews, status, imageUrl, lat, lng, opening_hours) {
            const detailContent = document.getElementById("detailContent");
            
            // Naikkan z-index dan tampilkan
            detailContent.style.zIndex = 50;
            detailContent.classList.remove("-translate-x-full");

            // Mengisi data dasar
            document.getElementById("detailImage").src = imageUrl || 'https://via.placeholder.com/400x192.png?text=Gambar+Tidak+Tersedia';
            document.getElementById("detailName").textContent = name;
            // Pastikan stickyDetailName ada sebelum menggunakannya
            if (stickyHeader && document.getElementById("stickyDetailName")) { // Check stickyHeader as well
                document.getElementById("stickyDetailName").textContent = name; 
            }
            document.getElementById("detailDescription").textContent = `Penjahit di ${address.split(',')[0]}`;
            document.getElementById("detailStatus").textContent = status;
            document.getElementById("detailAddress").textContent = address;
            document.getElementById("detailTelepon").textContent = telepon || 'Tidak ada nomor telepon';
            document.getElementById("detailOpeningHours").textContent = opening_hours || 'Tidak ada informasi jam buka';
            
            // Set tombol rute
            const routeButton = document.getElementById('routeButton');
            if (lat && lng) {
                routeButton.href = `https://www.google.com/maps/dir/?api=1&destination=${lat},${lng}`;
                routeButton.style.display = 'flex';
            } else {
                routeButton.style.display = 'none';
            }

            // Set Location ID di Form Review
            // Pastikan locationId ada sebelum menggunakannya
            const locationIdInput = document.getElementById("locationId");
            if (locationIdInput) {
                locationIdInput.value = id;
            }
            currentLocationId = id; // Simpan ID lokasi saat ini

            // Reset to summary tab
            if (summaryTab) { // Check if summaryTab exists
                summaryTab.click();
            }

            // Reset dan load reviews
            fetchReviews(id);

            // Reset scroll position
            if (scrollableContent) { // Check if scrollableContent exists
                scrollableContent.scrollTop = 0;
            }
        }

        // Event listener untuk tombol Bagikan
        // This listener is outside DOMContentLoaded, so it needs its own checks
        document.getElementById('shareButton').addEventListener('click', async () => {
            if (!currentLocationId) {
                alert('Pilih lokasi terlebih dahulu untuk berbagi.');
                return;
            }

            const shareUrl = `{{ url('/location') }}/${currentLocationId}`;
            const shareTitle = document.getElementById('detailName').textContent;
            const shareText = `Lihat penjahit ini di StitchLocator: ${shareTitle}`;

            if (navigator.share) {
                try {
                    await navigator.share({
                        title: shareTitle,
                        text: shareText,
                        url: shareUrl,
                    });
                    console.log('Berhasil berbagi!');
                } catch (error) {
                    console.error('Gagal berbagi:', error);
                }
            } else {
                // Fallback: Salin ke clipboard
                try {
                    await navigator.clipboard.writeText(shareUrl);
                    alert('Tautan telah disalin ke clipboard!');
                } catch (err) {
                    console.error('Gagal menyalin tautan:', err);
                    alert('Gagal menyalin tautan. Silakan salin secara manual: ' + shareUrl);
                }
            }
        });

        function fetchReviews(id) {
            fetch(`/reviews/${id}`)
                .then(response => {
                    if (!response.ok) {
                        console.error(`HTTP error! status: ${response.status}`, response);
                        throw new Error('Gagal memuat ulasan. Status: ' + response.status);
                    }
                    return response.json();
                })
                .then(reviews => {
                    const reviewList = document.getElementById("reviewList");
                    const reviewSummary = document.getElementById("reviewSummary");
                    const totalReviews = reviews.length;

                    // Reset konten
                    if (reviewList) reviewList.innerHTML = "";
                    if (reviewSummary) reviewSummary.innerHTML = '<h3 class="text-lg font-semibold mb-2">Ringkasan Ulasan</h3>';

                    if (totalReviews > 0) {
                        const ratingCounts = { 5: 0, 4: 0, 3: 0, 2: 0, 1: 0 };
                        let totalRating = 0;

                        reviews.forEach(review => {
                            ratingCounts[review.rating]++;
                            totalRating += review.rating;

                            // Tampilkan review individual
                            let reviewItem = document.createElement("li");
                            reviewItem.className = "bg-gray-50 p-3 rounded-lg border";
                            reviewItem.innerHTML = `
                                <div class="flex items-center mb-1">
                                    <div class="flex text-yellow-400">${'★'.repeat(review.rating)}${'☆'.repeat(5 - review.rating)}</div>
                                    <span class="text-gray-600 text-xs ml-auto">${new Date(review.created_at).toLocaleDateString()}</span>
                                </div>
                                <p class="text-gray-800 text-sm">${escapeHtml(review.review)}</p>
                            `;
                            if (reviewList) reviewList.appendChild(reviewItem);
                        });

                        const averageRating = (totalRating / totalReviews).toFixed(1);

                        // Update Rating di Header
                        const detailRatingEl = document.getElementById('detailRating');
                        const detailReviewCountEl = document.getElementById('detailReviewCount');
                        const detailRatingStarsEl = document.getElementById('detailRatingStars');

                        if (detailRatingEl) detailRatingEl.textContent = averageRating;
                        if (detailReviewCountEl) detailReviewCountEl.textContent = `(${totalReviews} ulasan)`;
                        if (detailRatingStarsEl) detailRatingStarsEl.innerHTML = '★'.repeat(Math.round(averageRating)) + '☆'.repeat(5 - Math.round(averageRating));

                        // --- UPDATE SIDEBAR ---
                        const sidebarRatingEl = document.getElementById(`sidebar-rating-${id}`);
                        const sidebarReviewsEl = document.getElementById(`sidebar-reviews-${id}`);
                        if (sidebarRatingEl) {
                            sidebarRatingEl.innerHTML = `★ ${averageRating}`;
                        }
                        if (sidebarReviewsEl) {
                            sidebarReviewsEl.textContent = `(${totalReviews} ulasan)`;
                        }
                        // --- END UPDATE SIDEBAR ---

                        // Buat Ringkasan Ulasan
                        for (let i = 5; i >= 1; i--) {
                            const percentage = totalReviews > 0 ? (ratingCounts[i] / totalReviews) * 100 : 0;
                            const summaryItem = document.createElement('div');
                            summaryItem.className = 'flex items-center text-sm';
                            summaryItem.innerHTML = `
                                <span class="w-8">${i} ★</span>
                                <div class="w-full bg-gray-200 rounded-full h-2 mx-2">
                                    <div class="bg-yellow-400 h-2 rounded-full" style="width: ${percentage}%"></div>
                                </div>
                                <span class="w-10 text-right text-gray-500">${Math.round(percentage)}%</span>
                            `;
                            if (reviewSummary) reviewSummary.appendChild(summaryItem);
                        }

                    } else {
                        if (reviewList) reviewList.innerHTML = "<li class='text-gray-500 text-sm'>Belum ada review untuk lokasi ini.</li>";
                        const detailRatingEl = document.getElementById('detailRating');
                        const detailReviewCountEl = document.getElementById('detailReviewCount');
                        const detailRatingStarsEl = document.getElementById('detailRatingStars');
                        if (detailRatingEl) detailRatingEl.textContent = '0.0';
                        if (detailReviewCountEl) detailReviewCountEl.textContent = `(0 ulasan)`;
                        if (detailRatingStarsEl) detailRatingStarsEl.innerHTML = '☆☆☆☆☆';
                        if (reviewSummary) reviewSummary.innerHTML += "<p class='text-gray-500 text-sm'>Jadilah yang pertama memberikan ulasan!</p>";
                    }
                })
                .catch(error => {
                    console.error("Error fetching reviews:", error);
                    const reviewList = document.getElementById("reviewList");
                    if (reviewList) {
                        reviewList.innerHTML = "<li class='text-red-500 text-sm'>Gagal memuat ulasan. Silakan coba lagi nanti.</li>";
                    }
                });
        }
    </script>
@endpush

@push('styles')
    <style>
        /* Custom scrollbar for WebKit browsers */
        .scrollbar-hide::-webkit-scrollbar {
            display: none;
        }
        /* Custom scrollbar for other browsers */
        .scrollbar-hide {
            -ms-overflow-style: none;  /* IE and Edge */
            scrollbar-width: none;  /* Firefox */
        }
    </style>
@endpush
