
<script>
// Inisialisasi Peta Leaflet
var map = L.map('map').setView([-6.9175, 107.6191], 13);

// Tile Layer OpenStreetMap
L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
    attribution: '&copy; OpenStreetMap contributors'
}).addTo(map);

// Data Lokasi dari Controller
var locations = @json($locations);

// Menyimpan daftar marker
var markers = {};

// Tambahkan marker ke peta
locations.forEach(function(location) {
    var marker = L.marker([location.lat, location.lng])
        .addTo(map)
        .bindPopup(`<b>${escapeHtml(location.name)}</b><br>${escapeHtml(location.address)}`);

    marker.locationData = location;

    marker.on('click', function() {
        console.log('Marker Click - Location ID:', location.id, 'Opening Hours:', location.opening_hours);
        showDetails(location.id, location.name, location.address, location.phone, 
                    location.rating, location.reviews, location.status, location.image_url, location.lat, location.lng, location.opening_hours);
    });

    markers[location.id] = marker;
});

// Fungsi Pencarian (Peta & Sidebar)
document.getElementById('searchbar').addEventListener('input', function(e) {
    var query = e.target.value.toLowerCase();

    // Filter marker di peta
    Object.values(markers).forEach(marker => {
        var matchName = marker.locationData.name.toLowerCase().includes(query);
        var matchAddress = marker.locationData.address.toLowerCase().includes(query);

        if (matchName || matchAddress) {
            marker.addTo(map);
        } else {
            map.removeLayer(marker);
        }
    });

    // Filter daftar lokasi di sidebar
    var items = document.querySelectorAll('.location-item');

    items.forEach(item => {
        var name = item.getAttribute('data-name');
        var address = item.getAttribute('data-address');

        if (name.includes(query) || address.includes(query)) {
            item.style.display = "flex";
        } else {
            item.style.display = "none";
        }
    });
});
</script>
