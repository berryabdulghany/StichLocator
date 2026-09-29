import L from './leaflet';

const escapeHtml = window.escapeHtml;

/**
 * Peta dasar StichLocator: tile OpenStreetMap yang warnanya diredam (lihat .map-tiles-muted di app.css)
 * dan tombol zoom di kanan bawah.
 */
export function createBaseMap(element, options = {}) {
    const map = L.map(element, { zoomControl: false, ...options });

    L.control.zoom({ position: 'bottomright' }).addTo(map);

    L.tileLayer('https://tile.openstreetmap.org/{z}/{x}/{y}.png', {
        attribution: '&copy; <a href="https://www.openstreetmap.org/copyright">OpenStreetMap</a>',
        className: 'map-tiles-muted',
        maxZoom: 19,
    }).addTo(map);

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
