// Halaman penuh detail penjahit: peta mini + interaksi detail
import L from './lib/leaflet';
import { createBaseMap, pinIcon } from './lib/map';
import { initDetail } from './detail';

const mini = document.getElementById('mini-map');

if (mini) {
    const lat = Number(mini.dataset.lat);
    const lng = Number(mini.dataset.lng);
    const map = createBaseMap(mini, { scrollWheelZoom: false }).setView([lat, lng], 16);

    L.marker([lat, lng], {
        icon: pinIcon({ id: 0, name: mini.dataset.name, rating: null, is_open: mini.dataset.open === '1' }, { active: true }),
        keyboard: false,
    }).addTo(map);
}

initDetail(document);
