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

const tr = window.t;
const escapeHtml = window.escapeHtml;
const { tailors, categories, homeUrl } = window.EXPLORER;

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
    activeId: null,
};

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

map.on('click', hidePreview);
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
};

let filtered = [];

/** Terapkan filter: perbarui marker di peta, lalu daftar. */
function applyFilters({ fit = true } = {}) {
    filtered = tailors.filter(matchesFilters);

    cluster.clearLayers();
    cluster.addLayers(filtered.map((tailor) => markers.get(tailor.id)));

    if (fit && !state.inBounds && filtered.length) {
        map.fitBounds(L.latLngBounds(filtered.map((t) => [t.lat, t.lng])), { padding: [60, 60], maxZoom: 15 });
    }

    renderList();
}

function renderList() {
    const bounds = map.getBounds();
    const visible = filtered
        .filter((tailor) => !state.inBounds || bounds.contains([tailor.lat, tailor.lng]))
        .sort(sorters[state.sort]);

    el.count.textContent = tr(visible.length === 1 ? ':count tailor found' : ':count tailors found', { count: visible.length });
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
    const price = tailor.price_from_text
        ? `<p class="mt-1.5 text-xs text-stone-500">${escapeHtml(tr('From'))} <span class="font-semibold text-stone-800">${escapeHtml(tailor.price_from_text)}</span></p>`
        : '';

    return `<li>
        <button type="button" class="tailor-card ${tailor.id === state.activeId ? 'is-active' : ''}" data-id="${tailor.id}">
            <img src="${escapeHtml(tailor.cover_url)}" alt="" loading="lazy" class="h-24 w-24 shrink-0 rounded-lg bg-navy-50 object-cover">
            <span class="min-w-0 flex-1">
                <span class="flex items-start justify-between gap-2">
                    <span class="truncate font-semibold text-stone-900">${escapeHtml(tailor.name)}</span>
                    ${rating}
                </span>
                <span class="mt-0.5 block text-xs font-medium ${tailor.is_open ? 'text-emerald-700' : 'text-red-600'}">${escapeHtml(tailor.status_text)}</span>
                <span class="mt-0.5 block truncate text-xs text-stone-500">${escapeHtml(tailor.address)}</span>
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
        initDetail(el.drawerBody, { onReviewSubmitted: () => loadDetail(tailor) });
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
    setActive(id);
    focusOnMap(tailor);
    setSheet(false);

    el.preview.innerHTML = `
        <div class="flex gap-3">
            <img src="${escapeHtml(tailor.cover_url)}" alt="" class="h-16 w-16 shrink-0 rounded-lg object-cover">
            <div class="min-w-0 flex-1">
                <p class="truncate text-sm font-semibold text-stone-900">${escapeHtml(tailor.name)}</p>
                <p class="text-xs ${tailor.is_open ? 'text-emerald-700' : 'text-red-600'}">${escapeHtml(tailor.status_text)}</p>
                ${tailor.price_from_text ? `<p class="text-xs text-stone-500">${escapeHtml(tr('From'))} <b class="font-semibold text-stone-800">${escapeHtml(tailor.price_from_text)}</b></p>` : ''}
            </div>
        </div>
        <div class="mt-2 grid grid-cols-2 gap-2">
            ${tailor.whatsapp_url ? `<a href="${escapeHtml(tailor.whatsapp_url)}" target="_blank" rel="noopener" class="btn-accent py-1.5"><i class="ti ti-brand-whatsapp" aria-hidden="true"></i>${escapeHtml(tr('Chat'))}</a>` : ''}
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
    Object.assign(state, { query: '', area: '', category: '', open: false, homeVisit: false, inBounds: false });
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

applyFilters();
