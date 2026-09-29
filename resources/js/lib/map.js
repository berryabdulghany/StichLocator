import L from './leaflet';
import 'maplibre-gl/dist/maplibre-gl.css';
import '@maplibre/maplibre-gl-leaflet';

const escapeHtml = window.escapeHtml;

/**
 * Gaya peta vektor OpenFreeMap "Liberty": latar putih bersih, taman hijau, air biru
 * (mirip Google Maps). Gratis, tanpa API key, berbasis data OpenStreetMap.
 */
const VECTOR_STYLE_URL = 'https://tiles.openfreemap.org/styles/liberty';
const VECTOR_ATTRIBUTION = '<a href="https://openfreemap.org" target="_blank" rel="noopener">OpenFreeMap</a> '
    + '&copy; <a href="https://www.openmaptiles.org/" target="_blank" rel="noopener">OpenMapTiles</a> '
    + '&copy; <a href="https://www.openstreetmap.org/copyright" target="_blank" rel="noopener">OpenStreetMap</a>';

/**
 * Warna gaya Liberty disesuaikan agar putih bersih ala Google Maps:
 * latar putih keabuan, jalan putih bertepi abu tipis, jalan tol kuning pucat,
 * taman hijau lembut, air biru muda. Diubah langsung di JSON gaya sebelum dipakai
 * sehingga peta tidak sempat tampil dengan warna asli.
 */
const CLEAN_PAINT = {
    background: { 'background-color': '#f7f7f5' },
    landuse_residential: { 'fill-opacity': 0 },
    building: { 'fill-color': '#e9e9e6' },
    park: { 'fill-color': '#d4ecd6' },
    landcover_grass: { 'fill-color': '#dcefd9' },
    landcover_wood: { 'fill-color': '#cfe8cc' },
    water: { 'fill-color': '#aad3f5' },
};

function cleanStyle(style) {
    for (const layer of style.layers) {
        if (CLEAN_PAINT[layer.id]) {
            layer.paint = { ...layer.paint, ...CLEAN_PAINT[layer.id] };
            continue;
        }

        if (layer.type !== 'line' || !/^(road|bridge|tunnel)_/.test(layer.id) || /rail|path_pedestrian/.test(layer.id)) {
            continue;
        }

        const casing = layer.id.includes('casing');
        const color = layer.id.includes('motorway')
            ? (casing ? '#f2c14e' : '#fde293')
            : (casing ? '#d9d9d5' : '#ffffff');

        layer.paint = { ...layer.paint, 'line-color': color };
    }

    return style;
}

function addRasterFallback(map) {
    L.tileLayer('https://tile.openstreetmap.org/{z}/{x}/{y}.png', {
        attribution: '&copy; <a href="https://www.openstreetmap.org/copyright">OpenStreetMap</a>',
        className: 'map-tiles-soft',
        maxZoom: 19,
    }).addTo(map);
}

function supportsWebGL() {
    try {
        const canvas = document.createElement('canvas');
        return Boolean(window.WebGLRenderingContext && (canvas.getContext('webgl2') || canvas.getContext('webgl')));
    } catch {
        return false;
    }
}

/**
 * Peta dasar StichLocator dengan tombol zoom di kanan bawah.
 * Memakai peta vektor OpenFreeMap; jika WebGL tidak tersedia, kembali ke tile OpenStreetMap biasa.
 */
export function createBaseMap(element, options = {}) {
    // maxZoom wajib ditetapkan di peta: layer vektor tidak menyetelnya, padahal markercluster membutuhkannya
    const map = L.map(element, { zoomControl: false, minZoom: 3, maxZoom: 19, ...options });

    L.control.zoom({ position: 'bottomright' }).addTo(map);

    if (supportsWebGL() && typeof L.maplibreGL === 'function') {
        fetch(VECTOR_STYLE_URL)
            .then((response) => {
                if (!response.ok) throw new Error(`HTTP ${response.status}`);
                return response.json();
            })
            .then((style) => {
                L.maplibreGL({ style: cleanStyle(style), attribution: VECTOR_ATTRIBUTION }).addTo(map);
            })
            .catch(() => addRasterFallback(map));
    } else {
        addRasterFallback(map);
    }

    return map;
}

/**
 * Marker pill: ikon jarum benang + rating, nama muncul saat hover/terpilih.
 * Penjahit yang sedang tutup ditampilkan pudar.
 */
export function pinIcon(tailor, { active = false } = {}) {
    const classes = ['sl-pin', tailor.is_open ? '' : 'is-closed', active ? 'is-active' : ''].join(' ');
    const rating = tailor.rating !== null ? tailor.rating.toFixed(1) : '';

    return L.divIcon({
        className: '',
        iconSize: null,
        html: `<div class="${classes}" data-pin="${tailor.id}">
                   <i class="ti ti-needle-thread" aria-hidden="true"></i>
                   ${rating ? `<span>${rating}</span>` : ''}
                   <span class="sl-pin-name">${escapeHtml(tailor.name)}</span>
               </div>`,
    });
}
