@extends('layouts.app')

@section('content')
    <div id="map" class="absolute inset-0 z-0" style="height: 100%; width: 100%;"></div>

    <!-- Detail Lokasi -->
    <div id="detailContent" class="absolute top-0 left-0 h-full w-full md:w-[400px] bg-white border-r border-stone-200 shadow-lg z-20 transform -translate-x-full transition-transform duration-300">
        <div class="h-full flex flex-col">
            <!-- Tombol Close -->
            <button onclick="closeDetails()" class="absolute top-3 right-3 z-50 flex h-8 w-8 items-center justify-center rounded-full bg-white text-stone-700 shadow hover:bg-stone-100"
                    aria-label="{{ __('Close') }}">
                <i class="ti ti-x text-lg" aria-hidden="true"></i>
            </button>

            <!-- Sticky Header (Initially Hidden) -->
            <div id="stickyDetailHeader" class="hidden p-4 border-b border-stone-200 bg-white">
                <h2 id="stickyDetailName" class="text-lg font-bold truncate"></h2>
            </div>

            <!-- Konten Scrollable -->
            <div id="scrollableContent" class="flex-grow overflow-y-auto scrollbar-hide">
                <!-- Gambar Lokasi -->
                <img id="detailImage" src="" alt="{{ __('Tailor photo') }}" class="w-full h-48 object-cover bg-navy-50"
                     onerror="this.onerror=null;this.src='https://placehold.co/800x384/E3ECF7/0C447C?text=StichLocator'">

                <div class="p-4">
                    <!-- Header -->
                    <div class="pb-3 border-b border-stone-200">
                        <h2 id="detailName" class="text-2xl font-bold text-stone-900"></h2>
                        <div class="flex items-center text-sm text-stone-600 mt-1">
                            <span id="detailRating" class="rating-star font-bold">0.0</span>
                            <div id="detailRatingStars" class="flex items-center ml-1 rating-star"></div>
                            <span id="detailReviewCount" class="ml-2"></span>
                        </div>
                        <p id="detailDescription" class="text-sm text-stone-500 mt-1"></p>
                        <span id="detailStatus" class="mt-2 badge-open"></span>
                    </div>

                    <!-- Tombol Aksi -->
                    <div class="grid grid-cols-3 gap-2 py-3 border-b border-stone-200 text-center">
                        <a id="routeButton" href="#" target="_blank" rel="noopener" class="flex flex-col items-center text-navy-700 hover:bg-navy-50 p-2 rounded-lg">
                            <i class="ti ti-route text-2xl" aria-hidden="true"></i>
                            <span class="text-xs mt-1">{{ __('Route') }}</span>
                        </a>
                        <button class="flex flex-col items-center text-navy-700 hover:bg-navy-50 p-2 rounded-lg">
                            <i class="ti ti-bookmark text-2xl" aria-hidden="true"></i>
                            <span class="text-xs mt-1">{{ __('Save') }}</span>
                        </button>
                        <button id="shareButton" class="flex flex-col items-center text-navy-700 hover:bg-navy-50 p-2 rounded-lg">
                            <i class="ti ti-share text-2xl" aria-hidden="true"></i>
                            <span class="text-xs mt-1">{{ __('Share') }}</span>
                        </button>
                    </div>

                    <!-- Tab Navigation -->
                    <div class="flex border-b border-stone-200">
                        <button id="summaryTab" class="flex-1 py-2 text-center text-sm font-semibold text-navy-700 border-b-2 border-navy-700 focus:outline-none">{{ __('Overview') }}</button>
                        <button id="reviewsTab" class="flex-1 py-2 text-center text-sm font-semibold text-stone-500 border-b-2 border-transparent focus:outline-none">{{ __('Reviews') }}</button>
                    </div>

                    <!-- Tab Content -->
                    <div id="summaryContent" class="py-3">
                        <!-- Informasi Detail -->
                        <div class="py-3 border-b border-stone-200 space-y-3 text-sm text-stone-700">
                            <div class="flex items-start gap-3">
                                <i class="ti ti-map-pin text-lg text-stone-400" aria-hidden="true"></i>
                                <span id="detailAddress"></span>
                            </div>
                            <div class="flex items-center gap-3">
                                <i class="ti ti-phone text-lg text-stone-400" aria-hidden="true"></i>
                                <span id="detailTelepon"></span>
                            </div>
                            <div class="flex items-center gap-3">
                                <i class="ti ti-clock text-lg text-stone-400" aria-hidden="true"></i>
                                <span id="detailOpeningHours"></span>
                            </div>
                        </div>
                        <!-- Ringkasan Ulasan -->
                        <div id="reviewSummary" class="py-3 border-b border-stone-200"></div>
                        <!-- Daftar Review -->
                        <div class="py-3">
                            <h3 class="text-base font-semibold">{{ __('Customer reviews') }}</h3>
                            <ul id="reviewList" class="space-y-3 mt-2"></ul>
                        </div>
                    </div>

                    <div id="reviewsContent" class="py-3 hidden">
                        <!-- Formulir Input Review -->
                        <div class="py-3">
                            <h3 class="text-base font-semibold">{{ __('Write a review') }}</h3>
                            @auth
                                <form id="reviewForm" class="review-form mt-2 space-y-2">
                                    @csrf
                                    <input type="hidden" name="location_id" id="locationId">
                                    <select name="rating" id="rating" class="input" aria-label="{{ __('Rating') }}">
                                        <option value="5">★★★★★ · {{ __('Excellent') }}</option>
                                        <option value="4">★★★★ · {{ __('Good') }}</option>
                                        <option value="3">★★★ · {{ __('Average') }}</option>
                                        <option value="2">★★ · {{ __('Poor') }}</option>
                                        <option value="1">★ · {{ __('Bad') }}</option>
                                    </select>
                                    <textarea name="review" id="review" rows="3" class="input" placeholder="{{ __('Share your experience...') }}" required></textarea>
                                    <button type="submit" class="btn-primary w-full">{{ __('Submit review') }}</button>
                                </form>
                            @else
                                <div class="mt-2 rounded-lg bg-navy-50 p-4 text-center text-navy-800">
                                    <p class="mb-3 text-sm">{{ __('You need to log in to write a review.') }}</p>
                                    <a href="{{ route('login') }}" class="btn-primary">{{ __('Log in now') }}</a>
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
        let reviewForm;

        const ACTIVE_TAB = ['text-navy-700', 'border-navy-700'];
        const INACTIVE_TAB = ['text-stone-500', 'border-transparent'];

        function reviewCountLabel(count) {
            return t(count === 1 ? ':count review' : ':count reviews', { count });
        }

        document.addEventListener('DOMContentLoaded', () => {
            // Inisialisasi elemen setelah DOM siap
            summaryTab = document.getElementById('summaryTab');
            reviewsTab = document.getElementById('reviewsTab');
            summaryContent = document.getElementById('summaryContent');
            reviewsContent = document.getElementById('reviewsContent');
            reviewForm = document.getElementById('reviewForm');

            scrollableContent = document.getElementById('scrollableContent');
            stickyHeader = document.getElementById('stickyDetailHeader');
            detailImage = document.getElementById('detailImage');

            // Event listeners for tabs
            if (summaryTab && reviewsTab && summaryContent && reviewsContent) {
                summaryTab.addEventListener('click', () => {
                    summaryTab.classList.add(...ACTIVE_TAB);
                    summaryTab.classList.remove(...INACTIVE_TAB);
                    reviewsTab.classList.add(...INACTIVE_TAB);
                    reviewsTab.classList.remove(...ACTIVE_TAB);

                    summaryContent.classList.remove('hidden');
                    reviewsContent.classList.add('hidden');
                });

                reviewsTab.addEventListener('click', () => {
                    reviewsTab.classList.add(...ACTIVE_TAB);
                    reviewsTab.classList.remove(...INACTIVE_TAB);
                    summaryTab.classList.add(...INACTIVE_TAB);
                    summaryTab.classList.remove(...ACTIVE_TAB);

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
                        headers: {
                            'X-CSRF-TOKEN': document.querySelector('input[name="_token"]').value,
                            'Accept': 'application/json',
                            'X-Requested-With': 'XMLHttpRequest',
                        }
                    })
                    .then(async response => {
                        const data = await response.json().catch(() => ({}));
                        if (!response.ok) {
                            throw new Error(data.message || t('Failed to submit review.'));
                        }
                        return data;
                    })
                    .then(() => {
                        document.getElementById('review').value = '';
                        document.getElementById('rating').value = '5';
                        fetchReviews(locationId); // Refresh reviews list and summary
                        summaryTab.click();
                    })
                    .catch(error => {
                        console.error('Error submitting review:', error);
                        alert(error.message);
                    });
                });
            }

            // Buka detail penjahit otomatis jika link dibagikan (?penjahit=ID)
            const sharedId = new URLSearchParams(window.location.search).get('penjahit');
            if (sharedId) {
                document.getElementById(`location-item-${parseInt(sharedId, 10)}`)?.click();
            }
        });

        function showDetails(id, name, address, telepon, rating, reviews, status, imageUrl, lat, lng, opening_hours) {
            const detailContent = document.getElementById("detailContent");

            // Naikkan z-index dan tampilkan
            detailContent.style.zIndex = 50;
            detailContent.classList.remove("-translate-x-full");

            // Mengisi data dasar
            document.getElementById("detailImage").src = imageUrl || '';
            document.getElementById("detailName").textContent = name;
            if (stickyHeader && document.getElementById("stickyDetailName")) {
                document.getElementById("stickyDetailName").textContent = name;
            }
            document.getElementById("detailDescription").textContent = t('Tailor in :area', { area: address.split(',')[0] });

            const statusEl = document.getElementById("detailStatus");
            statusEl.textContent = status === 'open' ? t('Open') : t('Closed');
            statusEl.className = 'mt-2 ' + (status === 'open' ? 'badge-open' : 'badge-closed');

            document.getElementById("detailAddress").textContent = address;
            document.getElementById("detailTelepon").textContent = telepon || t('No phone number');
            document.getElementById("detailOpeningHours").textContent = opening_hours || t('No opening hours information');

            // Set tombol rute
            const routeButton = document.getElementById('routeButton');
            if (lat && lng) {
                routeButton.href = `https://www.google.com/maps/dir/?api=1&destination=${lat},${lng}`;
                routeButton.style.display = 'flex';
            } else {
                routeButton.style.display = 'none';
            }

            // Set Location ID di Form Review
            const locationIdInput = document.getElementById("locationId");
            if (locationIdInput) {
                locationIdInput.value = id;
            }
            currentLocationId = id;

            // Reset to summary tab
            if (summaryTab) {
                summaryTab.click();
            }

            // Reset dan load reviews
            fetchReviews(id);

            // Reset scroll position
            if (scrollableContent) {
                scrollableContent.scrollTop = 0;
            }
        }

        // Event listener untuk tombol Bagikan
        document.getElementById('shareButton').addEventListener('click', async () => {
            if (!currentLocationId) {
                return;
            }

            const shareUrl = `{{ route('dashboard') }}?penjahit=${currentLocationId}`;
            const shareTitle = document.getElementById('detailName').textContent;
            const shareText = t('Check out this tailor on StichLocator: :name', { name: shareTitle });

            if (navigator.share) {
                try {
                    await navigator.share({ title: shareTitle, text: shareText, url: shareUrl });
                } catch (error) {
                    // Pengguna membatalkan dialog bagikan
                }
            } else {
                try {
                    await navigator.clipboard.writeText(shareUrl);
                    alert(t('Link copied to clipboard.'));
                } catch (err) {
                    alert(t('Could not copy the link. Copy it manually: :url', { url: shareUrl }));
                }
            }
        });

        function fetchReviews(id) {
            fetch(`/reviews/${id}`, { headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' } })
                .then(response => {
                    if (!response.ok) {
                        throw new Error('HTTP ' + response.status);
                    }
                    return response.json();
                })
                .then(reviews => {
                    const reviewList = document.getElementById("reviewList");
                    const reviewSummary = document.getElementById("reviewSummary");
                    const totalReviews = reviews.length;
                    const detailRatingEl = document.getElementById('detailRating');
                    const detailReviewCountEl = document.getElementById('detailReviewCount');
                    const detailRatingStarsEl = document.getElementById('detailRatingStars');

                    // Reset konten
                    reviewList.innerHTML = "";
                    reviewSummary.innerHTML = `<h3 class="text-base font-semibold mb-2">${escapeHtml(t('Review summary'))}</h3>`;

                    if (totalReviews > 0) {
                        const ratingCounts = { 5: 0, 4: 0, 3: 0, 2: 0, 1: 0 };
                        let totalRating = 0;

                        reviews.forEach(review => {
                            ratingCounts[review.rating]++;
                            totalRating += review.rating;

                            // Tampilkan review individual
                            const reviewItem = document.createElement("li");
                            reviewItem.className = "rounded-lg border border-stone-200 p-3";
                            reviewItem.innerHTML = `
                                <div class="flex items-center mb-1 gap-2">
                                    <div class="rating-star">${'★'.repeat(review.rating)}${'☆'.repeat(5 - review.rating)}</div>
                                    <span class="text-xs font-medium text-stone-600">${escapeHtml(review.user_name)}</span>
                                    <span class="text-stone-400 text-xs ml-auto">${new Date(review.created_at).toLocaleDateString(window.APP_LOCALE)}</span>
                                </div>
                                <p class="text-stone-700 text-sm">${escapeHtml(review.review)}</p>
                            `;
                            reviewList.appendChild(reviewItem);
                        });

                        const averageRating = (totalRating / totalReviews).toFixed(1);

                        // Update Rating di Header
                        detailRatingEl.textContent = averageRating;
                        detailReviewCountEl.textContent = `(${reviewCountLabel(totalReviews)})`;
                        detailRatingStarsEl.textContent = '★'.repeat(Math.round(averageRating)) + '☆'.repeat(5 - Math.round(averageRating));

                        // Update sidebar
                        const sidebarRatingEl = document.getElementById(`sidebar-rating-${id}`);
                        const sidebarReviewsEl = document.getElementById(`sidebar-reviews-${id}`);
                        if (sidebarRatingEl) sidebarRatingEl.textContent = `★ ${averageRating}`;
                        if (sidebarReviewsEl) sidebarReviewsEl.textContent = `(${reviewCountLabel(totalReviews)})`;

                        // Buat Ringkasan Ulasan
                        for (let i = 5; i >= 1; i--) {
                            const percentage = (ratingCounts[i] / totalReviews) * 100;
                            const summaryItem = document.createElement('div');
                            summaryItem.className = 'flex items-center text-sm';
                            summaryItem.innerHTML = `
                                <span class="w-8 text-stone-600">${i} ★</span>
                                <div class="w-full bg-stone-200 rounded-full h-2 mx-2">
                                    <div class="bg-terra-500 h-2 rounded-full" style="width: ${percentage}%"></div>
                                </div>
                                <span class="w-10 text-right text-stone-500">${Math.round(percentage)}%</span>
                            `;
                            reviewSummary.appendChild(summaryItem);
                        }
                    } else {
                        reviewList.innerHTML = `<li class="text-stone-500 text-sm">${escapeHtml(t('No reviews for this tailor yet.'))}</li>`;
                        detailRatingEl.textContent = '0.0';
                        detailReviewCountEl.textContent = `(${reviewCountLabel(0)})`;
                        detailRatingStarsEl.textContent = '☆☆☆☆☆';
                        reviewSummary.innerHTML += `<p class="text-stone-500 text-sm">${escapeHtml(t('Be the first to leave a review!'))}</p>`;
                    }
                })
                .catch(error => {
                    console.error("Error fetching reviews:", error);
                    document.getElementById("reviewList").innerHTML =
                        `<li class="text-red-600 text-sm">${escapeHtml(t('Failed to load reviews. Please try again later.'))}</li>`;
                });
        }
    </script>
@endpush
