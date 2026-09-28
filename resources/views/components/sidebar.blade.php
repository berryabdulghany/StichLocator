<div id="sidebar" class="relative z-30 h-full transition-all duration-300 bg-white w-full md:w-[400px] flex flex-col border-r border-gray-200">
    <!-- Header Sidebar -->
    <div class="p-4 border-b border-gray-200 sticky top-0 bg-white z-10">
        <h1 class="text-xl font-bold text-gray-800">Temukan Penjahit</h1>
        <div class="relative mt-2">
            <input id="searchbar" type="text"
                class="w-full bg-gray-100 border-2 border-gray-200 rounded-lg py-2 pl-10 pr-4 focus:outline-none focus:bg-white focus:border-blue-500"
                placeholder="Cari nama atau alamat...">
            <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                <svg class="w-5 h-5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path></svg>
            </div>
        </div>
        <!-- Filter Dropdowns -->
        <div class="flex mt-2 space-x-2">
            <!-- Rating Filter Dropdown -->
            <div class="w-1/2">
                <select id="rating-filter" class="w-full bg-gray-100 border-2 border-gray-200 rounded-lg py-2 px-3 focus:outline-none focus:bg-white focus:border-blue-500 text-sm text-gray-600">
                    <option value="0">Semua Rating</option>
                    <option value="5">Bintang 5</option>
                    <option value="4">Bintang 4</option>
                    <option value="3">Bintang 3</option>
                    <option value="2">Bintang 2</option>
                    <option value="1">Bintang 1</option>
                </select>
            </div>
            <!-- Status Filter Dropdown -->
            <div class="w-1/2">
                <select id="status-filter" class="w-full bg-gray-100 border-2 border-gray-200 rounded-lg py-2 px-3 focus:outline-none focus:bg-white focus:border-blue-500 text-sm text-gray-600">
                    <option value="all">Semua Status</option>
                    <option value="Buka">Buka</option>
                    <option value="Tutup">Tutup</option>
                </select>
            </div>
        </div>
    </div>

    <!-- Daftar Lokasi Scrollable -->
    <div id="location-list" class="flex-grow overflow-y-auto p-2 scrollbar-hide">
        @if($locations->isEmpty())
            <div class="p-4 text-center text-gray-500">
                <p>Tidak ada lokasi yang ditemukan.</p>
            </div>
        @else
            @foreach ($locations as $location)
            <div id="location-item-{{ $location->id }}" 
                 class="location-item p-3 hover:bg-gray-100 cursor-pointer border-b border-gray-200 border-l-4 border-transparent transition-all duration-200"
                 onclick="showDetailsAndFly(this, {{ (int) $location->id }}, {{ Js::from($location->name) }}, {{ Js::from($location->address) }}, {{ Js::from($location->telepon) }}, {{ (float) ($location->rating ?? 0) }}, {{ (int) ($location->reviews_count ?? 0) }}, {{ Js::from($location->status) }}, {{ Js::from($location->image_url) }}, {{ (float) $location->lat }}, {{ (float) $location->lng }}, {{ Js::from($location->opening_hours) }})"
                 data-name="{{ strtolower($location->name) }}"
                 data-address="{{ strtolower($location->address) }}"
                 data-rating="{{ $location->rating ?? 0 }}"
                 data-status="{{ $location->status }}">
                
                <div class="flex items-start space-x-4">
                    <img src="{{ $location->image_url ?? 'https://via.placeholder.com/100x100.png?text=No+Image' }}" alt="{{ $location->name }}" class="w-24 h-24 rounded-lg object-cover border">
                    <div class="flex-grow">
                        <h3 class="font-bold text-gray-800 text-md">{{ $loop->iteration }}. {{ $location->name }}</h3>
                        <p class="text-sm text-gray-600 mt-1">{{ $location->address }}</p>
                        <div class="flex items-center mt-2 text-sm">
                            <span id="sidebar-rating-{{ $location->id }}" class="text-yellow-500 font-bold">★ {{ number_format($location->rating, 1) ?? 'N/A' }}</span>
                            <span id="sidebar-reviews-{{ $location->id }}" class="text-gray-500 ml-2">({{ $location->reviews_count ?? 0 }} ulasan)</span>
                        </div>
                        <p class="text-sm font-semibold mt-1 {{ $location->status == 'Buka' ? 'text-green-600' : 'text-red-600' }}">{{ $location->status }}</p>
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
        item.classList.remove('bg-blue-50', 'border-blue-500', 'shadow-lg');
        item.classList.add('border-transparent');
    });

    // Tambahkan status aktif ke item yang diklik
    if (element) {
        element.classList.add('bg-blue-50', 'border-blue-500', 'shadow-lg');
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

    // Filter marker di peta
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
