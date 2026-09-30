/**
 * Halaman peta StichLocator: katalog penjahit + peta.
 * Data ringkas penjahit dikirim server lewat window.EXPLORER, lalu pencarian,
 * filter, urutan, dan sinkronisasi daftar <-> marker dilakukan di browser.
 */
import L from './lib/leaflet';
import 'leaflet.markercluster';
import 'leaflet.markercluster/dist/MarkerCluster.css';
import { createBaseMap, pinIcon } from './lib/map';
import { initDetail } from './detail';
import { distanceMeters, formatDistance, formatDuration, pointOnCircle } from './lib/geo';
import { getSavedIds, onSavedChange } from './lib/saved';
import { initTracking } from './lib/track';

initTracking();

const tr = window.t;
const escapeHtml = window.escapeHtml;
const { tailors, categories, homeUrl, routeUrl, routingEnabled } = window.EXPLORER;

const byId = new Map(tailors.map((tailor) => [tailor.id, tailor]));
const categoryLabel = Object.fromEntries(categories.map((category) => [category.value, category.label]));
const desktopQuery = window.matchMedia('(min-width: 1024px)');
const isDesktop = () => desktopQuery.matches;

const state = {
    query: '',
    area: '',
    category: '',
    open: false,
    homeVisit: false,
    sort: 'recommended',
    inBounds: false,
    saved: false,
    activeId: null,
    location: null, // { lat, lng } setelah "Lokasi saya"
    radius: null,   // meter; null = tanpa batas radius
};

let savedIds = new Set(getSavedIds());

const el = {
    searchForm: document.getElementById('search-form'),
    query: document.getElementById('search-query'),
    area: document.getElementById('search-area'),
    categoryTabs: document.querySelectorAll('[data-category]'),
    filterChips: document.querySelectorAll('[data-filter]'),
    sort: document.getElementById('sort-select'),
    boundsToggle: document.getElementById('bounds-toggle'),
    panel: document.getElementById('results-panel'),
    handle: document.getElementById('sheet-handle'),
    count: document.getElementById('results-count'),
    list: document.getElementById('tailor-list'),
    empty: document.getElementById('empty-state'),
    reset: document.getElementById('reset-filters'),
    preview: document.getElementById('map-preview'),
    drawer: document.getElementById('detail-drawer'),
    drawerBody: document.getElementById('drawer-body'),
    drawerClose: document.getElementById('drawer-close'),
    permalink: document.getElementById('drawer-permalink'),
    radiusBar: document.getElementById('radius-bar'),
    radiusButtons: document.querySelectorAll('[data-radius]'),
    routeCard: document.getElementById('route-card'),
    toast: document.getElementById('toast'),
    savedCount: document.getElementById('saved-count'),
    boundsControl: document.getElementById('bounds-control'),
    pickLocation: document.getElementById('pick-location'),
};

/*
|--------------------------------------------------------------------------
| Peta, marker, dan cluster "gulungan benang"
|--------------------------------------------------------------------------
*/

const map = createBaseMap('map');

const cluster = L.markerClusterGroup({
    showCoverageOnHover: false,
    maxClusterRadius: 45,
    iconCreateFunction: (group) => L.divIcon({
        className: '',
        iconSize: [40, 40],
        html: `<div class="sl-spool" aria-label="${escapeHtml(tr(':count tailors', { count: group.getChildCount() }))}">${group.getChildCount()}</div>`,
    }),
});
map.addLayer(cluster);

const markers = new Map();
tailors.forEach((tailor) => {
    const marker = L.marker([tailor.lat, tailor.lng], { icon: pinIcon(tailor), title: tailor.name, riseOnHover: true });
    marker.on('click', () => (isDesktop() ? openTailor(tailor.id) : showPreview(tailor.id)));
    marker.on('mouseover', () => setHover(tailor.id, true));
    marker.on('mouseout', () => setHover(tailor.id, false));
    markers.set(tailor.id, marker);
});

map.on('click', (event) => {
    if (pickingLocation) {
        stopPickingLocation();
        setUserLocation({ lat: event.latlng.lat, lng: event.latlng.lng }, { manual: true });
        showToast(tr('Location updated.'), 2500);
        return;
    }
    hidePreview();
});
map.on('moveend', () => state.inBounds && renderList());

/*
|--------------------------------------------------------------------------
| Filter & urutan
|--------------------------------------------------------------------------
*/

function matchesFilters(tailor) {
    if (state.category && !tailor.categories.includes(state.category)) return false;
    if (state.area && tailor.area !== state.area) return false;
    if (state.open && !tailor.is_open) return false;
    if (state.homeVisit && !tailor.home_visit) return false;
    if (state.saved && !savedIds.has(tailor.id)) return false;
    if (state.location && state.radius && tailor.distance > state.radius) return false;

    if (state.query) {
        const haystack = [tailor.name, tailor.address, tailor.services, ...tailor.categories.map((c) => categoryLabel[c])]
            .join(' ')
            .toLowerCase();
        if (!state.query.split(/\s+/).every((word) => haystack.includes(word))) return false;
    }

    return true;
}

const sorters = {
    recommended: (a, b) => (b.is_open - a.is_open) || ((b.rating ?? 0) - (a.rating ?? 0)) || (b.reviews_count - a.reviews_count),
    rating: (a, b) => ((b.rating ?? 0) - (a.rating ?? 0)) || (b.reviews_count - a.reviews_count),
    price: (a, b) => (a.price_from ?? Infinity) - (b.price_from ?? Infinity),
    reviews: (a, b) => b.reviews_count - a.reviews_count,
    distance: (a, b) => (a.distance ?? Infinity) - (b.distance ?? Infinity),
};

let filtered = [];

/** Terapkan filter: perbarui marker di peta, lalu daftar. */
function applyFilters({ fit = true } = {}) {
    filtered = tailors.filter(matchesFilters);

    cluster.clearLayers();
    cluster.addLayers(filtered.map((tailor) => markers.get(tailor.id)));

    if (fit && !state.inBounds) {
        if (radiusCircle) {
            map.fitBounds(radiusCircle.getBounds(), { padding: [40, 40] });
        } else if (filtered.length) {
            const points = filtered.map((t) => [t.lat, t.lng]);
            if (state.location) points.push([state.location.lat, state.location.lng]);
            map.fitBounds(L.latLngBounds(points), { padding: [60, 60], maxZoom: 15 });
        }
    }

    renderList();
}

function renderList() {
    const bounds = map.getBounds();
    const visible = filtered
        .filter((tailor) => !state.inBounds || bounds.contains([tailor.lat, tailor.lng]))
        .sort(sorters[state.sort]);

    const one = visible.length === 1;
    el.count.textContent = state.location && state.radius
        ? tr(one ? ':count tailor within :radius' : ':count tailors within :radius', { count: visible.length, radius: formatDistance(state.radius) })
        : tr(one ? ':count tailor found' : ':count tailors found', { count: visible.length });
    el.list.innerHTML = visible.map(cardHtml).join('');
    el.list.classList.toggle('hidden', visible.length === 0);
    el.empty.classList.toggle('hidden', visible.length > 0);
    el.empty.classList.toggle('flex', visible.length === 0);
}

function cardHtml(tailor) {
    const tags = tailor.categories.slice(0, 2)
        .map((category) => `<span class="badge-tag">${escapeHtml(categoryLabel[category])}</span>`)
        .join('');
    const homeVisit = tailor.home_visit
        ? `<span class="badge bg-navy-50 text-navy-700"><i class="ti ti-home-move" aria-hidden="true"></i>${escapeHtml(tr('Home visit'))}</span>`
        : '';
    const rating = tailor.rating !== null
        ? `<span class="rating-star shrink-0 text-sm font-semibold">★ ${tailor.rating.toFixed(1)} <span class="font-normal text-stone-400">(${tailor.reviews_count})</span></span>`
        : '';
    const distance = tailor.distance !== undefined ? `${escapeHtml(formatDistance(tailor.distance))} · ` : '';
    const savedBadge = savedIds.has(tailor.id)
        ? `<span class="absolute right-1 top-1 flex h-6 w-6 items-center justify-center rounded-full bg-white/90 text-terra-600" title="${escapeHtml(tr('Saved'))}"><i class="ti ti-bookmark-filled text-sm" aria-hidden="true"></i></span>`
        : '';
    const price = tailor.price_from_text
        ? `<p class="mt-1.5 text-xs text-stone-500">${escapeHtml(tr('From'))} <span class="font-semibold text-stone-800">${escapeHtml(tailor.price_from_text)}</span></p>`
        : '';

    return `<li>
        <button type="button" class="tailor-card ${tailor.id === state.activeId ? 'is-active' : ''}" data-id="${tailor.id}">
            <span class="relative shrink-0">
                <img src="${escapeHtml(tailor.cover_url)}" alt="" loading="lazy" class="h-24 w-24 rounded-lg bg-navy-50 object-cover">
                ${savedBadge}
            </span>
            <span class="min-w-0 flex-1">
                <span class="flex items-start justify-between gap-2">
                    <span class="truncate font-semibold text-stone-900">${escapeHtml(tailor.name)}</span>
                    ${rating}
                </span>
                <span class="mt-0.5 block text-xs font-medium ${tailor.is_open ? 'text-emerald-700' : 'text-red-600'}">${escapeHtml(tailor.status_text)}</span>
                <span class="mt-0.5 block truncate text-xs text-stone-500">${distance}${escapeHtml(tailor.address)}</span>
                <span class="mt-2 flex flex-wrap items-center gap-1">${tags}${homeVisit}</span>
                ${price}
            </span>
        </button>
    </li>`;
}

/*
|--------------------------------------------------------------------------
| Sorotan: hover & penjahit terpilih (sinkron daftar <-> marker)
|--------------------------------------------------------------------------
*/

function pinElement(id) {
    return markers.get(id)?.getElement()?.querySelector('.sl-pin');
}

function setHover(id, on) {
    pinElement(id)?.classList.toggle('is-hover', on);
    el.list.querySelector(`[data-id="${id}"]`)?.classList.toggle('bg-stone-50', on);
}

function setActive(id) {
    const previous = state.activeId;
    state.activeId = id;

    // Ikon dibuat ulang agar status aktif tetap ada saat cluster dibentuk ulang
    [previous, id].filter(Boolean).forEach((markerId) => {
        markers.get(markerId).setIcon(pinIcon(byId.get(markerId), { active: markerId === id }));
    });

    el.list.querySelectorAll('.tailor-card').forEach((card) => {
        card.classList.toggle('is-active', Number(card.dataset.id) === id);
    });
}

/** Fokuskan peta ke penjahit; di desktop digeser ke kiri supaya tidak tertutup drawer. */
function focusOnMap(tailor) {
    const zoom = Math.max(map.getZoom(), 15);
    const point = map.project([tailor.lat, tailor.lng], zoom).add([isDesktop() ? 210 : 0, 0]);
    map.flyTo(map.unproject(point, zoom), zoom, { duration: 0.6 });
}

/*
|--------------------------------------------------------------------------
| Drawer detail (dimuat dari /penjahit/{slug} via AJAX)
|--------------------------------------------------------------------------
*/

let detailRequest = null;

function openTailor(id, { push = true } = {}) {
    const tailor = byId.get(id);
    if (!tailor) return;

    if (routeState && routeState.id !== id) clearRoute();
    hidePreview();
    setSheet(false);
    setActive(id);
    focusOnMap(tailor);

    el.drawer.classList.add('is-open');
    el.drawer.setAttribute('aria-hidden', 'false');
    el.permalink.href = tailor.detail_url;
    document.title = `${tailor.name} · StichLocator`;

    if (push) {
        history.pushState({ tailorId: id }, '', tailor.detail_url);
    }

    loadDetail(tailor);
}

async function loadDetail(tailor) {
    detailRequest?.abort();
    detailRequest = new AbortController();
    el.drawerBody.innerHTML = `<div class="flex h-40 items-center justify-center text-stone-400"><i class="ti ti-loader-2 animate-spin text-2xl" aria-hidden="true"></i></div>`;

    try {
        const response = await fetch(tailor.detail_url, {
            headers: { 'X-Requested-With': 'XMLHttpRequest', Accept: 'text/html' },
            signal: detailRequest.signal,
        });
        if (!response.ok) throw new Error(response.status);

        // HTML berasal dari server kita sendiri (Blade, sudah di-escape)
        el.drawerBody.innerHTML = await response.text();
        el.drawerBody.scrollTop = 0;
        initDetail(el.drawerBody, {
            onReviewSubmitted: () => loadDetail(tailor),
            onRoute: (id) => showRoute(id),
        });
        el.drawerClose.focus({ preventScroll: true });
    } catch (error) {
        if (error.name === 'AbortError') return;
        el.drawerBody.innerHTML = `<p class="p-6 text-sm text-red-600">${escapeHtml(tr('Failed to load details. Please try again.'))}</p>`;
    }
}

function closeDrawer({ push = true } = {}) {
    if (!el.drawer.classList.contains('is-open')) return;

    el.drawer.classList.remove('is-open');
    el.drawer.setAttribute('aria-hidden', 'true');
    detailRequest?.abort();
    setActive(null);
    document.title = 'StichLocator';

    if (push) {
        history.pushState({}, '', homeUrl);
    }
}

el.drawerClose.addEventListener('click', () => closeDrawer());
document.addEventListener('keydown', (event) => event.key === 'Escape' && closeDrawer());
window.addEventListener('popstate', (event) => {
    const id = event.state?.tailorId;
    id ? openTailor(id, { push: false }) : closeDrawer({ push: false });
});

/*
|--------------------------------------------------------------------------
| Kartu preview saat marker diketuk (HP)
|--------------------------------------------------------------------------
*/

function showPreview(id) {
    const tailor = byId.get(id);
    if (routeState && routeState.id !== id) clearRoute();
    setActive(id);
    focusOnMap(tailor);
    setSheet(false);

    el.preview.innerHTML = `
        <div class="flex gap-3">
            <img src="${escapeHtml(tailor.cover_url)}" alt="" class="h-16 w-16 shrink-0 rounded-lg object-cover">
            <div class="min-w-0 flex-1">
                <p class="truncate text-sm font-semibold text-stone-900">${escapeHtml(tailor.name)}</p>
                <p class="text-xs ${tailor.is_open ? 'text-emerald-700' : 'text-red-600'}">${escapeHtml(tailor.status_text)}${tailor.distance !== undefined ? ` · <span class="text-stone-500">${escapeHtml(formatDistance(tailor.distance))}</span>` : ''}</p>
                ${tailor.price_from_text ? `<p class="text-xs text-stone-500">${escapeHtml(tr('From'))} <b class="font-semibold text-stone-800">${escapeHtml(tailor.price_from_text)}</b></p>` : ''}
            </div>
        </div>
        <div class="mt-2 grid grid-cols-2 gap-2">
            ${tailor.whatsapp_url ? `<a href="${escapeHtml(tailor.whatsapp_url)}" target="_blank" rel="noopener" data-track="whatsapp" data-track-url="${escapeHtml(tailor.track_url)}" class="btn-accent py-1.5"><i class="ti ti-brand-whatsapp" aria-hidden="true"></i>${escapeHtml(tr('Chat'))}</a>` : ''}
            <button type="button" data-open-detail class="btn-outline py-1.5">${escapeHtml(tr('Details'))}</button>
        </div>`;
    el.preview.classList.remove('hidden');
    el.preview.querySelector('[data-open-detail]').addEventListener('click', () => openTailor(id));
}

function hidePreview() {
    if (el.preview.classList.contains('hidden')) return;
    el.preview.classList.add('hidden');
    if (!el.drawer.classList.contains('is-open')) setActive(null);
}

/*
|--------------------------------------------------------------------------
| Bottom sheet daftar penjahit (HP): ketuk atau tarik pegangan
|--------------------------------------------------------------------------
*/

const PEEK_HEIGHT = 120; // sama dengan 7.5rem di .results-sheet
let sheetExpanded = false;
let drag = null;

function setSheet(expanded) {
    sheetExpanded = expanded;
    el.panel.classList.toggle('is-expanded', expanded);
    el.handle.setAttribute('aria-expanded', String(expanded));
}

el.handle.addEventListener('pointerdown', (event) => {
    if (isDesktop()) return;
    drag = { startY: event.clientY, base: sheetExpanded ? 0 : el.panel.offsetHeight - PEEK_HEIGHT, moved: false };
    el.handle.setPointerCapture(event.pointerId);
    el.panel.classList.add('is-dragging');
});

el.handle.addEventListener('pointermove', (event) => {
    if (!drag) return;
    const dy = event.clientY - drag.startY;
    if (Math.abs(dy) > 5) drag.moved = true;
    const y = Math.min(Math.max(drag.base + dy, 0), el.panel.offsetHeight - PEEK_HEIGHT);
    el.panel.style.transform = `translateY(${y}px)`;
});

el.handle.addEventListener('pointerup', (event) => {
    if (!drag) return;
    const dy = event.clientY - drag.startY;
    el.panel.classList.remove('is-dragging');
    el.panel.style.transform = '';
    setSheet(drag.moved ? (sheetExpanded ? dy < 80 : dy < -80) : !sheetExpanded);
    drag = null;
});

// Pastikan status drag selalu direset (misalnya gesture dibatalkan browser),
// supaya klik berikutnya tidak "tertangkap" pegangan sheet.
['pointercancel', 'lostpointercapture'].forEach((type) => {
    el.handle.addEventListener(type, () => {
        if (!drag) return;
        el.panel.classList.remove('is-dragging');
        el.panel.style.transform = '';
        drag = null;
    });
});

desktopQuery.addEventListener('change', () => {
    setSheet(false);
    hidePreview();
    map.invalidateSize();
});

/*
|--------------------------------------------------------------------------
| Event: pencarian, kategori, filter, urutan, daftar
|--------------------------------------------------------------------------
*/

let queryTimer;
el.query.addEventListener('input', () => {
    clearTimeout(queryTimer);
    queryTimer = setTimeout(() => {
        state.query = el.query.value.trim().toLowerCase();
        applyFilters();
    }, 200);
});

el.area.addEventListener('change', () => {
    state.area = el.area.value;
    applyFilters();
});

el.searchForm.addEventListener('submit', (event) => {
    event.preventDefault();
    state.query = el.query.value.trim().toLowerCase();
    el.query.blur();
    applyFilters();
    if (!isDesktop()) setSheet(true);
});

el.categoryTabs.forEach((tab) => {
    tab.addEventListener('click', () => {
        state.category = tab.dataset.category;
        el.categoryTabs.forEach((other) => {
            other.classList.toggle('is-active', other === tab);
            other.setAttribute('aria-pressed', String(other === tab));
        });
        applyFilters();
    });
});

el.filterChips.forEach((chip) => {
    chip.addEventListener('click', () => {
        const key = chip.dataset.filter;
        state[key] = !state[key];
        chip.classList.toggle('is-active', state[key]);
        chip.setAttribute('aria-pressed', String(state[key]));
        applyFilters();
    });
});

el.sort.addEventListener('change', () => {
    state.sort = el.sort.value;
    renderList();
});

el.boundsToggle.addEventListener('change', () => {
    state.inBounds = el.boundsToggle.checked;
    renderList();
});

el.reset.addEventListener('click', () => {
    Object.assign(state, { query: '', area: '', category: '', open: false, homeVisit: false, saved: false, inBounds: false });
    if (state.location) setRadius(null, { apply: false });
    el.query.value = '';
    el.area.value = '';
    el.boundsToggle.checked = false;
    el.categoryTabs.forEach((tab) => {
        tab.classList.toggle('is-active', tab.dataset.category === '');
        tab.setAttribute('aria-pressed', String(tab.dataset.category === ''));
    });
    el.filterChips.forEach((chip) => {
        chip.classList.remove('is-active');
        chip.setAttribute('aria-pressed', 'false');
    });
    applyFilters();
});

el.list.addEventListener('click', (event) => {
    const card = event.target.closest('.tailor-card');
    if (card) openTailor(Number(card.dataset.id));
});
el.list.addEventListener('mouseover', (event) => {
    const card = event.target.closest('.tailor-card');
    if (card) setHover(Number(card.dataset.id), true);
});
el.list.addEventListener('mouseout', (event) => {
    const card = event.target.closest('.tailor-card');
    if (card && !card.contains(event.relatedTarget)) setHover(Number(card.dataset.id), false);
});

/*
|--------------------------------------------------------------------------
| Pesan singkat (toast)
|--------------------------------------------------------------------------
*/

let toastTimer;
function showToast(message, duration = 4500) {
    el.toast.textContent = message;
    el.toast.classList.remove('hidden');
    clearTimeout(toastTimer);
    toastTimer = setTimeout(() => el.toast.classList.add('hidden'), duration);
}

/*
|--------------------------------------------------------------------------
| Lokasi saya & radius "pita ukur"
|--------------------------------------------------------------------------
*/

let userMarker = null;
let accuracyCircle = null;
let radiusCircle = null;
let radiusLabel = null;

// Lokasi dari browser di laptop/PC (tanpa GPS) ditebak dari Wi-Fi/IP dan bisa meleset beberapa km.
// Di atas batas ini, tampilkan lingkaran akurasi dan ajak pengguna menggeser titik biru.
const POOR_ACCURACY_M = 500;

// Tombol "Lokasi saya" sebagai kontrol Leaflet (ditumpuk di atas tombol zoom)
const LocateControl = L.Control.extend({
    options: { position: 'bottomright' },
    onAdd() {
        const button = L.DomUtil.create('button', 'sl-locate');
        button.type = 'button';
        button.title = tr('My location');
        button.setAttribute('aria-label', tr('My location'));
        button.innerHTML = '<i class="ti ti-current-location" aria-hidden="true"></i>';
        L.DomEvent.disableClickPropagation(button);
        L.DomEvent.on(button, 'click', () => locateUser());
        this.button = button;
        return button;
    },
});
const locateControl = new LocateControl().addTo(map);

/** Minta lokasi pengguna. Mengembalikan true jika berhasil. */
function locateUser() {
    return new Promise((resolve) => {
        if (!navigator.geolocation) {
            showToast(tr('Your browser does not support location.'));
            resolve(false);
            return;
        }

        const button = locateControl.button;
        button.classList.add('is-loading');

        navigator.geolocation.getCurrentPosition(
            (position) => {
                button.classList.remove('is-loading');
                setUserLocation(
                    { lat: position.coords.latitude, lng: position.coords.longitude },
                    { accuracy: position.coords.accuracy },
                );
                resolve(true);
            },
            (error) => {
                button.classList.remove('is-loading');
                showToast(error.code === error.PERMISSION_DENIED
                    ? tr('Location permission denied. Allow location access in your browser to see nearby tailors.')
                    : tr('Could not find your location. Please try again.'));
                resolve(false);
            },
            { enableHighAccuracy: true, timeout: 15000, maximumAge: 0 },
        );
    });
}

/**
 * @param {{ lat: number, lng: number }} location
 * @param {{ accuracy?: number|null, manual?: boolean }} options
 *   accuracy: perkiraan akurasi dari browser (meter)
 *   manual  : lokasi dikoreksi pengguna dengan menggeser titik biru (radius yang dipilih dipertahankan)
 */
function setUserLocation(location, { accuracy = null, manual = false } = {}) {
    state.location = location;
    el.toast.classList.add('hidden');
    tailors.forEach((tailor) => {
        tailor.distance = distanceMeters(location, tailor);
    });

    const latLng = [location.lat, location.lng];
    if (userMarker) {
        userMarker.setLatLng(latLng);
    } else {
        // Titik biru bisa digeser untuk mengoreksi lokasi yang meleset
        userMarker = L.marker(latLng, {
            icon: L.divIcon({ className: '', iconSize: null, html: '<span class="sl-user-dot is-draggable"></span>' }),
            draggable: true,
            autoPan: true,
            keyboard: false,
            title: tr('Your location. Drag to correct it.'),
            zIndexOffset: 500,
        }).addTo(map);

        userMarker.on('dragend', () => {
            const position = userMarker.getLatLng();
            setUserLocation({ lat: position.lat, lng: position.lng }, { manual: true });
        });
    }
    locateControl.button.classList.add('is-active');

    // Lingkaran akurasi (hanya untuk lokasi dari browser, bukan hasil geser manual)
    accuracyCircle?.remove();
    accuracyCircle = null;
    if (!manual && accuracy > 50) {
        accuracyCircle = L.circle(latLng, {
            radius: accuracy,
            stroke: false,
            fillColor: '#185FA5',
            fillOpacity: 0.08,
            interactive: false,
        }).addTo(map);
    }

    if (manual) {
        // Pertahankan radius pilihan pengguna, geser lingkarannya (peta ikut menyesuaikan),
        // dan hitung ulang rute jika sedang tampil
        drawRadius();
        applyFilters();
        if (routeState) showRoute(routeState.id, routeState.mode);
        return;
    }

    // Aktifkan urutan "Terdekat"
    const nearestOption = el.sort.querySelector('option[value="distance"]');
    nearestOption.hidden = false;
    nearestOption.disabled = false;
    el.sort.value = 'distance';
    state.sort = 'distance';

    el.radiusBar.classList.remove('hidden');
    el.radiusBar.classList.add('flex');

    // Radius awal: yang terkecil dan berisi minimal 3 penjahit
    const countWithin = (radius) => tailors.filter((tailor) => tailor.distance <= radius).length;
    const radius = [2000, 5000, 10000].find((r) => countWithin(r) >= 3) ?? null;
    setRadius(radius, { apply: false });

    if (accuracy > POOR_ACCURACY_M) {
        showToast(tr('Your browser location is approximate (±:distance). Drag the blue dot or use "Correct location" to fix it.', {
            distance: formatDistance(accuracy),
        }), 9000);
    } else if (radius === null) {
        showToast(tr('No tailors near you yet. Showing all tailors sorted by distance.'));
    }

    applyFilters();
}

function setRadius(radius, { apply = true } = {}) {
    state.radius = radius;
    el.radiusButtons.forEach((button) => {
        const value = button.dataset.radius ? Number(button.dataset.radius) : null;
        button.classList.toggle('chip-active', value === radius);
        button.setAttribute('aria-pressed', String(value === radius));
    });
    drawRadius();

    if (apply) applyFilters();
}

function drawRadius() {
    radiusCircle?.remove();
    radiusLabel?.remove();
    radiusCircle = null;
    radiusLabel = null;

    if (!state.location || !state.radius) return;

    radiusCircle = L.circle([state.location.lat, state.location.lng], {
        radius: state.radius,
        color: '#0C447C',
        weight: 2,
        dashArray: '6 6', // garis jahitan
        fillColor: '#185FA5',
        fillOpacity: 0.05,
        interactive: false,
    }).addTo(map);

    const edge = pointOnCircle(state.location, state.radius, 45);
    radiusLabel = L.marker([edge.lat, edge.lng], {
        icon: L.divIcon({
            className: '',
            iconSize: null,
            html: `<span class="sl-radius-label"><i class="ti ti-ruler-measure" aria-hidden="true"></i>${escapeHtml(formatDistance(state.radius))}</span>`,
        }),
        interactive: false,
        keyboard: false,
    }).addTo(map);
}

el.radiusButtons.forEach((button) => {
    button.addEventListener('click', () => setRadius(button.dataset.radius ? Number(button.dataset.radius) : null));
});

/*
| Koreksi lokasi: alternatif menggeser titik biru (lebih mudah di HP) — ketuk peta di posisi yang benar.
*/
let pickingLocation = false;

function startPickingLocation() {
    pickingLocation = true;
    map.getContainer().classList.add('is-picking');
    setSheet(false);
    hidePreview();
    showToast(tr('Tap the map at your location.'), 10000);
}

function stopPickingLocation() {
    pickingLocation = false;
    map.getContainer().classList.remove('is-picking');
}

el.pickLocation.addEventListener('click', startPickingLocation);
document.addEventListener('keydown', (event) => {
    if (event.key === 'Escape' && pickingLocation) {
        stopPickingLocation();
        el.toast.classList.add('hidden');
    }
});

/*
|--------------------------------------------------------------------------
| Penjahit tersimpan (localStorage)
|--------------------------------------------------------------------------
*/

function updateSavedCount() {
    el.savedCount.textContent = savedIds.size || '';
    el.savedCount.classList.toggle('hidden', savedIds.size === 0);
}

onSavedChange((ids) => {
    savedIds = new Set(ids);
    updateSavedCount();
    state.saved ? applyFilters({ fit: false }) : renderList();
});
updateSavedCount();

/*
|--------------------------------------------------------------------------
| Pratinjau rute (OpenRouteService lewat /rute) + navigasi di Google Maps
|--------------------------------------------------------------------------
*/

let routeLayers = [];
let routeRequest = null;
let routeState = null; // { id, mode }

function googleDirectionsUrl(tailor, mode = 'driving') {
    const params = new URLSearchParams({ api: '1', destination: `${tailor.lat},${tailor.lng}`, travelmode: mode });
    if (state.location) params.set('origin', `${state.location.lat},${state.location.lng}`);

    return `https://www.google.com/maps/dir/?${params}`;
}

async function showRoute(id, mode = 'driving') {
    const tailor = byId.get(id);
    if (!tailor) return;

    routeState = { id, mode };

    if (!state.location && !(await locateUser())) {
        renderRouteCard(tailor, mode, { error: tr('Allow location access to preview the route, or open it directly in Google Maps.') });
        return;
    }

    // Seperti Google Maps: drawer ditutup supaya rute terlihat penuh (bisa dibuka lagi lewat tombol Detail)
    closeDrawer();
    hidePreview();
    setSheet(false);
    setActive(id);

    const from = state.location;

    // Tanpa API key: tampilkan garis lurus + jarak perkiraan
    if (!routingEnabled) {
        drawRoute([[from.lat, from.lng], [tailor.lat, tailor.lng]]);
        renderRouteCard(tailor, mode, { distance: tailor.distance, straight: true });
        return;
    }

    renderRouteCard(tailor, mode, { loading: true });
    routeRequest?.abort();
    routeRequest = new AbortController();

    try {
        const params = new URLSearchParams({
            from_lat: from.lat, from_lng: from.lng, to_lat: tailor.lat, to_lng: tailor.lng, mode,
        });
        const response = await fetch(`${routeUrl}?${params}`, {
            headers: { Accept: 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
            signal: routeRequest.signal,
        });
        const data = await response.json().catch(() => ({}));

        if (!response.ok) {
            throw new Error(data.message || tr('Could not calculate the route. Please try again.'));
        }

        drawRoute(data.coordinates);
        renderRouteCard(tailor, mode, { distance: data.distance, duration: data.duration });
    } catch (error) {
        if (error.name === 'AbortError') return;
        clearRouteLine();
        renderRouteCard(tailor, mode, { error: error.message });
    }
}

function drawRoute(latLngs) {
    clearRouteLine();

    // Garis putih di bawah + garis putus-putus terracotta (motif jahitan) di atas
    routeLayers = [
        L.polyline(latLngs, { color: '#ffffff', weight: 8, opacity: 0.9, interactive: false }),
        L.polyline(latLngs, { color: '#D85A30', weight: 4, dashArray: '10 7', lineCap: 'round', interactive: false }),
    ].map((layer) => layer.addTo(map));

    map.fitBounds(L.latLngBounds(latLngs), {
        paddingTopLeft: [40, 80],
        paddingBottomRight: isDesktop() ? [40, 220] : [40, 300],
    });
}

function clearRouteLine() {
    routeLayers.forEach((layer) => layer.remove());
    routeLayers = [];
}

function clearRoute() {
    routeRequest?.abort();
    clearRouteLine();
    routeState = null;
    el.routeCard.classList.add('hidden');
    el.boundsControl.style.display = '';
}

function renderRouteCard(tailor, mode, { loading = false, error = null, distance = null, duration = null, straight = false } = {}) {
    const modeButton = (value, icon, label) => `
        <button type="button" data-mode="${value}" aria-pressed="${mode === value}"
                class="inline-flex items-center gap-1 rounded-md px-2.5 py-1 font-medium transition ${mode === value ? 'bg-navy-700 text-white' : 'text-stone-600 hover:bg-stone-100'}">
            <i class="ti ti-${icon}" aria-hidden="true"></i>${escapeHtml(tr(label))}
        </button>`;

    let summary = '';
    if (loading) {
        summary = `<p class="mt-2 flex items-center gap-2 text-sm text-stone-500"><i class="ti ti-loader-2 animate-spin" aria-hidden="true"></i>${escapeHtml(tr('Calculating route...'))}</p>`;
    } else if (error) {
        summary = `<p class="mt-2 text-sm text-red-600">${escapeHtml(error)}</p>`;
    } else if (straight) {
        summary = `<p class="mt-2 text-lg font-bold text-stone-900">± ${escapeHtml(formatDistance(distance))}</p>
                   <p class="text-xs text-stone-500">${escapeHtml(tr('Straight-line distance. Open Google Maps for the actual route.'))}</p>`;
    } else if (distance !== null) {
        summary = `<p class="mt-2 text-lg font-bold text-stone-900">${escapeHtml(formatDuration(duration))} <span class="text-sm font-medium text-stone-500">· ${escapeHtml(formatDistance(distance))}</span></p>`;
    }

    el.routeCard.innerHTML = `
        <div class="flex items-start justify-between gap-2">
            <div class="min-w-0">
                <p class="text-xs text-stone-500">${escapeHtml(tr('Route to'))}</p>
                <p class="truncate font-semibold text-stone-900">${escapeHtml(tailor.name)}</p>
            </div>
            <button type="button" data-route-close class="btn-ghost -mr-2 -mt-1 px-2" aria-label="${escapeHtml(tr('Close'))}">
                <i class="ti ti-x" aria-hidden="true"></i>
            </button>
        </div>
        ${routingEnabled && state.location ? `
            <div class="mt-2 inline-flex rounded-lg border border-stone-200 p-0.5 text-xs" role="group" aria-label="${escapeHtml(tr('Travel mode'))}">
                ${modeButton('driving', 'car', 'Vehicle')}
                ${modeButton('walking', 'walk', 'Walking')}
            </div>` : ''}
        ${summary}
        <div class="mt-3 flex gap-2">
            <a href="${escapeHtml(googleDirectionsUrl(tailor, mode))}" target="_blank" rel="noopener" data-track="route" data-track-url="${escapeHtml(tailor.track_url)}" class="btn-primary flex-1">
                <i class="ti ti-navigation" aria-hidden="true"></i>${escapeHtml(tr('Start navigation in Google Maps'))}
            </a>
            <button type="button" data-route-detail class="btn-outline">${escapeHtml(tr('Details'))}</button>
        </div>`;

    el.routeCard.classList.remove('hidden');
    el.boundsControl.style.display = 'none';
}

el.routeCard.addEventListener('click', (event) => {
    const modeButton = event.target.closest('[data-mode]');
    if (modeButton && routeState) {
        showRoute(routeState.id, modeButton.dataset.mode);
        return;
    }
    if (event.target.closest('[data-route-close]')) {
        clearRoute();
        return;
    }
    if (event.target.closest('[data-route-detail]') && routeState) {
        openTailor(routeState.id);
    }
});


/*
|--------------------------------------------------------------------------
| Filter awal dari URL (misalnya dari landing page)
| ?q=kebaya  ?kategori=permak  ?wilayah=Coblong  ?dekat=1
|--------------------------------------------------------------------------
*/

const initialParams = new URLSearchParams(window.location.search);

if (initialParams.get('q')) {
    el.query.value = initialParams.get('q');
    state.query = el.query.value.trim().toLowerCase();
}

const initialCategory = initialParams.get('kategori');
if (initialCategory && categoryLabel[initialCategory]) {
    state.category = initialCategory;
    el.categoryTabs.forEach((tab) => {
        const active = tab.dataset.category === initialCategory;
        tab.classList.toggle('is-active', active);
        tab.setAttribute('aria-pressed', String(active));
    });
}

const initialArea = initialParams.get('wilayah');
if (initialArea && [...el.area.options].some((option) => option.value === initialArea)) {
    el.area.value = initialArea;
    state.area = initialArea;
}

applyFilters();

if (initialParams.get('dekat') === '1') {
    locateUser();
}
