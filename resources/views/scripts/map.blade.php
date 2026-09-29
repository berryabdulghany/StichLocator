<script>
// Data lokasi dari controller
var locations = @json($locations);

// Inisialisasi peta Leaflet (tombol zoom dipindah ke kanan bawah agar tidak tertutup panel)
var map = L.map('map', { zoomControl: false }).setView([-6.2, 106.83], 12);
L.control.zoom({ position: 'bottomright' }).addTo(map);

// Tile OpenStreetMap (gratis, tanpa API key). Warnanya diredam lewat CSS (.map-tiles-muted)
// agar peta terlihat clean dan marker penjahit lebih menonjol.
L.tileLayer('https://tile.openstreetmap.org/{z}/{x}/{y}.png', {
    attribution: '&copy; <a href="https://www.openstreetmap.org/copyright">OpenStreetMap</a>',
    className: 'map-tiles-muted',
    maxZoom: 19
}).addTo(map);

// Marker berikon jarum benang: navy saat buka, abu-abu saat tutup
function tailorIcon(isOpen) {
    return L.divIcon({
        className: '',
        html: `<span class="flex h-8 w-8 items-center justify-center rounded-full border-2 border-white shadow-md ${isOpen ? 'bg-navy-700' : 'bg-stone-400'} text-white">
                   <i class="ti ti-needle-thread text-base" aria-hidden="true"></i>
               </span>`,
        iconSize: [32, 32],
        iconAnchor: [16, 16],
        popupAnchor: [0, -16]
    });
}

// Menyimpan daftar marker
var markers = {};

locations.forEach(function(location) {
    var marker = L.marker([location.lat, location.lng], { icon: tailorIcon(location.status === 'open') })
        .addTo(map)
        .bindPopup(`<b>${escapeHtml(location.name)}</b><br>${escapeHtml(location.address)}`);

    marker.locationData = location;

    marker.on('click', function() {
        var item = document.getElementById('location-item-' + location.id);
        showDetailsAndFly(item, location.id, location.name, location.address, location.telepon,
                          location.rating, location.reviews_count, location.status, location.cover_url,
                          location.lat, location.lng, location.opening_hours);
    });

    markers[location.id] = marker;
});

// Posisikan peta agar semua penjahit terlihat
if (locations.length) {
    map.fitBounds(L.latLngBounds(locations.map(l => [l.lat, l.lng])), { padding: [60, 60], maxZoom: 15 });
}
</script>
