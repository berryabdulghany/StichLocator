<div id="sidebar" class="relative z-30 h-full transition-all duration-300 bg-white w-full md:w-[400px] flex flex-col border-r border-stone-200">
    <!-- Header Sidebar -->
    <div class="p-4 border-b border-stone-200 sticky top-0 bg-white z-10">
        <x-logo />
        <h1 class="mt-3 text-sm font-medium text-stone-500">{{ __('Find a tailor near you') }}</h1>
        <div class="relative mt-2">
            <input id="searchbar" type="text" class="input rounded-full pl-10"
                   placeholder="{{ __('Search by name or address...') }}" aria-label="{{ __('Search tailors') }}">
            <div class="absolute inset-y-0 left-0 pl-3.5 flex items-center pointer-events-none text-stone-400">
                <i class="ti ti-search text-lg" aria-hidden="true"></i>
            </div>
        </div>
        <!-- Filter Dropdowns -->
        <div class="flex mt-2 gap-2">
            <select id="rating-filter" class="input w-1/2 text-stone-600" aria-label="{{ __('Filter by rating') }}">
                <option value="0">{{ __('All ratings') }}</option>
                @for ($star = 5; $star >= 1; $star--)
                    <option value="{{ $star }}">{{ __(':count stars', ['count' => $star]) }}</option>
                @endfor
            </select>
            <select id="status-filter" class="input w-1/2 text-stone-600" aria-label="{{ __('Filter by status') }}">
                <option value="all">{{ __('All statuses') }}</option>
                <option value="open">{{ __('Open') }}</option>
                <option value="closed">{{ __('Closed') }}</option>
            </select>
        </div>
    </div>

    <!-- Daftar Lokasi Scrollable -->
    <div id="location-list" class="flex-grow overflow-y-auto p-2 scrollbar-hide">
        @if($locations->isEmpty())
            <div class="p-4 text-center text-stone-500">
                <p>{{ __('No tailors found.') }}</p>
            </div>
        @else
            @foreach ($locations as $location)
            <div id="location-item-{{ $location->id }}"
                 class="location-item p-3 rounded-lg hover:bg-stone-50 cursor-pointer border-l-4 border-transparent transition-all duration-200"
                 onclick="showDetailsAndFly(this, {{ (int) $location->id }}, {{ Js::from($location->name) }}, {{ Js::from($location->address) }}, {{ Js::from($location->telepon) }}, {{ (float) ($location->rating ?? 0) }}, {{ (int) ($location->reviews_count ?? 0) }}, {{ Js::from($location->status) }}, {{ Js::from($location->cover_url) }}, {{ (float) $location->lat }}, {{ (float) $location->lng }}, {{ Js::from($location->opening_hours) }})"
                 data-name="{{ strtolower($location->name) }}"
                 data-address="{{ strtolower($location->address) }}"
                 data-rating="{{ $location->rating ?? 0 }}"
                 data-status="{{ $location->status }}">

                <div class="flex items-start gap-4">
                    <img src="{{ $location->cover_url }}" alt="{{ $location->name }}" loading="lazy"
                         onerror="this.onerror=null;this.src='https://placehold.co/200x200/E3ECF7/0C447C?text=StichLocator'"
                         class="w-20 h-20 rounded-lg object-cover bg-navy-50">
                    <div class="flex-grow min-w-0">
                        <h3 class="font-semibold text-stone-800 truncate">{{ $location->name }}</h3>
                        <p class="text-sm text-stone-500 mt-0.5 line-clamp-2">{{ $location->address }}</p>
                        <div class="flex items-center mt-1.5 text-sm">
                            <span id="sidebar-rating-{{ $location->id }}" class="rating-star font-semibold">★ {{ number_format($location->rating ?? 0, 1) }}</span>
                            <span id="sidebar-reviews-{{ $location->id }}" class="text-stone-500 ml-2">({{ trans_choice(':count review|:count reviews', $location->reviews_count ?? 0, ['count' => $location->reviews_count ?? 0]) }})</span>
                        </div>
                        <span class="mt-1.5 {{ $location->status === 'open' ? 'badge-open' : 'badge-closed' }}">
                            {{ $location->status === 'open' ? __('Open') : __('Closed') }}
                        </span>
                    </div>
                </div>
            </div>
            @endforeach
        @endif
    </div>
</div>

<script>
function showDetailsAndFly(element, id, name, address, telepon, rating, reviews_count, status, imageUrl, lat, lng, opening_hours) {
    // --- START ACTIVE STATE MANAGEMENT ---
    // Hapus status aktif dari semua item lain
    const allItems = document.querySelectorAll('.location-item');
    allItems.forEach(item => {
        item.classList.remove('bg-navy-50', 'border-navy-600');
        item.classList.add('border-transparent');
    });

    // Tambahkan status aktif ke item yang diklik
    if (element) {
        element.classList.add('bg-navy-50', 'border-navy-600');
        element.classList.remove('border-transparent');
    }
    // --- END ACTIVE STATE MANAGEMENT ---

    // Panggil fungsi showDetails yang sudah ada di dashboard.blade.php
    if (typeof showDetails === 'function') {
        showDetails(id, name, address, telepon, rating, reviews_count, status, imageUrl, lat, lng, opening_hours);
    }

    // Terbang ke lokasi di peta
    if (typeof map !== 'undefined' && lat && lng) {
        map.flyTo([lat, lng], 15); // Angka 15 adalah level zoom
    }
}

// Fungsi filter utama
function applyFilters() {
    const searchQuery = document.getElementById('searchbar').value.toLowerCase();
    const selectedRating = parseFloat(document.getElementById('rating-filter').value);
    const selectedStatus = document.getElementById('status-filter').value;

    const locationList = document.getElementById('location-list');
    const items = locationList.querySelectorAll('.location-item');

    items.forEach(item => {
        const name = item.dataset.name;
        const address = item.dataset.address;
        const rating = parseFloat(item.dataset.rating);
        const status = item.dataset.status;

        const matchesSearch = name.includes(searchQuery) || address.includes(searchQuery);
        const matchesRating = selectedRating === 0 || Math.floor(rating) === selectedRating;
        const matchesStatus = selectedStatus === 'all' || status === selectedStatus;

        if (matchesSearch && matchesRating && matchesStatus) {
            item.style.display = 'flex';
        } else {
            item.style.display = 'none';
        }
    });

    // Filter marker di peta (halaman tanpa peta, misalnya profil, dilewati)
    if (typeof markers === 'undefined') {
        return;
    }

    Object.values(markers).forEach(marker => {
        const locationData = marker.locationData;
        const matchesSearch = locationData.name.toLowerCase().includes(searchQuery) || locationData.address.toLowerCase().includes(searchQuery);
        const rating = locationData.rating ?? 0;
        const status = locationData.status;

        const matchesRating = selectedRating === 0 || Math.floor(rating) === selectedRating;
        const matchesStatus = selectedStatus === 'all' || status === selectedStatus;

        if (matchesSearch && matchesRating && matchesStatus) {
            marker.addTo(map);
        } else {
            map.removeLayer(marker);
        }
    });
}

// Event listeners untuk filter
document.getElementById('searchbar').addEventListener('input', applyFilters);
document.getElementById('rating-filter').addEventListener('change', applyFilters);
document.getElementById('status-filter').addEventListener('change', applyFilters);

// Panggil filter saat halaman dimuat untuk memastikan keadaan awal benar
document.addEventListener('DOMContentLoaded', applyFilters);

</script>
