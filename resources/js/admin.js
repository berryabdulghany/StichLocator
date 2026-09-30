/**
 * Interaksi panel admin: menu HP, konfirmasi hapus, dan form penjahit
 * (pemilih lokasi di peta, jam buka, baris layanan, pratinjau sampul).
 */
const t = window.t;

/*
| Sidebar di layar kecil
*/
const sidebar = document.getElementById('admin-sidebar');
const backdrop = document.getElementById('admin-sidebar-backdrop');
const toggle = document.getElementById('admin-sidebar-toggle');

function setSidebar(open) {
    sidebar?.classList.toggle('-translate-x-full', !open);
    backdrop?.classList.toggle('hidden', !open);
    toggle?.setAttribute('aria-expanded', String(open));
}

toggle?.addEventListener('click', () => setSidebar(sidebar.classList.contains('-translate-x-full')));
backdrop?.addEventListener('click', () => setSidebar(false));
document.addEventListener('keydown', (event) => event.key === 'Escape' && setSidebar(false));

/*
| Konfirmasi sebelum aksi berbahaya: <form data-confirm="Yakin?">
*/
document.addEventListener('submit', (event) => {
    const message = event.target.dataset?.confirm;
    if (message && !window.confirm(message)) {
        event.preventDefault();
    }
});

/*
| Tab (dasbor mitra): <section data-tabs> berisi [role=tab][data-tab] dan [role=tabpanel]
*/
document.querySelectorAll('[data-tabs]').forEach((root) => {
    const tabs = [...root.querySelectorAll('[role="tab"]')];
    const select = (tab) => {
        tabs.forEach((other) => {
            const selected = other === tab;
            other.setAttribute('aria-selected', String(selected));
            other.tabIndex = selected ? 0 : -1;
            document.getElementById(other.getAttribute('aria-controls')).hidden = !selected;
        });
    };

    tabs.forEach((tab, index) => {
        tab.tabIndex = tab.getAttribute('aria-selected') === 'true' ? 0 : -1;
        tab.addEventListener('click', () => select(tab));
        tab.addEventListener('keydown', (event) => {
            const step = { ArrowRight: 1, ArrowDown: 1, ArrowLeft: -1, ArrowUp: -1 }[event.key];
            if (!step) return;
            event.preventDefault();
            const next = tabs[(index + step + tabs.length) % tabs.length];
            select(next);
            next.focus();
        });
    });
});

/*
| Salin link undangan mitra (halaman edit penjahit)
*/
document.querySelector('[data-copy-invite]')?.addEventListener('click', async (event) => {
    const button = event.currentTarget;
    const input = document.querySelector('[data-invite-input]');
    const label = button.querySelector('[data-copy-label]');

    try {
        await navigator.clipboard.writeText(input.value);
    } catch {
        input.select();
        document.execCommand('copy');
    }
    label.textContent = t('Copied!');
    setTimeout(() => { label.textContent = t('Copy'); }, 2000);
});

/*
| Form penjahit
*/
const tailorForm = document.getElementById('tailor-form');

if (tailorForm) {
    initHours();
    initServices();
    initCoverPreview();
    initGallery();
    initLocationPicker();
}

/** Checkbox "Libur" menonaktifkan input jam di barisnya */
function initHours() {
    tailorForm.querySelectorAll('[data-hours-row]').forEach((row) => {
        const checkbox = row.querySelector('[data-closed-toggle]');
        const inputs = row.querySelectorAll('[data-hours-inputs] input');

        const sync = () => {
            inputs.forEach((input) => {
                input.disabled = checkbox.checked;
            });
            row.querySelector('[data-hours-inputs]').classList.toggle('opacity-40', checkbox.checked);
        };

        checkbox.addEventListener('change', sync);
        sync();
    });
}

/** Tambah / hapus baris layanan dari <template> */
function initServices() {
    const list = tailorForm.querySelector('[data-services]');
    const template = tailorForm.querySelector('[data-service-template]');
    const empty = tailorForm.querySelector('[data-services-empty]');
    let nextIndex = list.querySelectorAll('[data-service-row]').length;

    const syncEmpty = () => empty.classList.toggle('hidden', list.children.length > 0);

    tailorForm.querySelector('[data-add-service]').addEventListener('click', () => {
        const html = template.innerHTML.replaceAll('__INDEX__', String(nextIndex++));
        list.insertAdjacentHTML('beforeend', html);
        syncEmpty();
        list.lastElementChild.querySelector('input[type="text"]')?.focus();
    });

    list.addEventListener('click', (event) => {
        const button = event.target.closest('[data-remove-service]');
        if (!button) return;
        button.closest('[data-service-row]').remove();
        syncEmpty();
    });
}

/** Pratinjau foto sampul sebelum disimpan */
function initCoverPreview() {
    const input = document.getElementById('cover');
    const preview = document.getElementById('cover-preview');
    const placeholder = document.getElementById('cover-placeholder');

    input?.addEventListener('change', () => {
        const file = input.files?.[0];
        if (!file) return;
        preview.src = URL.createObjectURL(file);
        preview.classList.remove('hidden');
        placeholder.classList.add('hidden');
    });
}

/** Galeri: tombol naik/turun memindahkan foto (input hidden photo_order[] ikut pindah) */
function initGallery() {
    const gallery = tailorForm.querySelector('[data-gallery]');
    if (!gallery) return;

    const syncButtons = () => {
        const items = [...gallery.children];
        items.forEach((item, index) => {
            item.querySelector('[data-photo-up]').disabled = index === 0;
            item.querySelector('[data-photo-down]').disabled = index === items.length - 1;
        });
    };

    gallery.addEventListener('click', (event) => {
        const button = event.target.closest('[data-photo-up], [data-photo-down]');
        if (!button) return;
        const item = button.closest('[data-photo]');
        if (button.hasAttribute('data-photo-up')) {
            item.previousElementSibling?.before(item);
        } else {
            item.nextElementSibling?.after(item);
        }
        syncButtons();
        button.disabled ? item.querySelector('button:not(:disabled)')?.focus() : button.focus();
    });

    // Foto yang dicentang hapus dibuat redup agar jelas
    gallery.addEventListener('change', (event) => {
        if (event.target.name === 'photos_delete[]') {
            event.target.closest('[data-photo]').classList.toggle('opacity-50', event.target.checked);
        }
    });

    syncButtons();
}

/** Pemilih lokasi: klik peta atau geser pin; input lat/lng ikut berubah (dan sebaliknya) */
async function initLocationPicker() {
    const element = document.getElementById('tailor-map');
    if (!element) return;

    // Library peta cukup besar, jadi hanya dimuat di halaman form penjahit
    const [{ default: L }, { createBaseMap }] = await Promise.all([
        import('./lib/leaflet'),
        import('./lib/map'),
    ]);

    const latInput = document.getElementById('lat');
    const lngInput = document.getElementById('lng');
    const start = [Number(latInput.value) || -6.9175, Number(lngInput.value) || 107.6191];

    const map = createBaseMap(element).setView(start, 15);
    const marker = L.marker(start, {
        draggable: true,
        title: t('Drag to the tailor location'),
        icon: L.divIcon({
            className: '',
            iconSize: null,
            html: '<span class="sl-pin is-active"><i class="ti ti-needle-thread" aria-hidden="true"></i></span>',
        }),
    }).addTo(map);

    const writeInputs = ({ lat, lng }) => {
        latInput.value = lat.toFixed(6);
        lngInput.value = lng.toFixed(6);
    };

    marker.on('dragend', () => writeInputs(marker.getLatLng()));
    map.on('click', (event) => {
        marker.setLatLng(event.latlng);
        writeInputs(event.latlng);
    });

    [latInput, lngInput].forEach((input) => {
        input.addEventListener('change', () => {
            const lat = Number(latInput.value);
            const lng = Number(lngInput.value);
            if (Number.isFinite(lat) && Number.isFinite(lng)) {
                marker.setLatLng([lat, lng]);
                map.panTo([lat, lng]);
            }
        });
    });

    initGeocoder((lat, lng) => {
        marker.setLatLng([lat, lng]);
        map.setView([lat, lng], 17);
        writeInputs({ lat, lng });
    });
}

/**
 * Cari alamat lewat Nominatim (OpenStreetMap). Sesuai kebijakan pemakaiannya,
 * pencarian hanya dijalankan saat tombol/Enter ditekan (bukan setiap ketikan).
 */
function initGeocoder(onPick) {
    const box = tailorForm.querySelector('[data-geocode]');
    if (!box) return;

    const input = box.querySelector('[data-geocode-input]');
    const status = box.querySelector('[data-geocode-status]');
    const results = box.querySelector('[data-geocode-results]');
    let controller = null;

    const setStatus = (text) => {
        status.textContent = text;
        status.hidden = !text;
    };
    const closeResults = () => {
        results.hidden = true;
        results.innerHTML = '';
    };

    const search = async (query) => {
        query = query.trim();
        if (query.length < 3) {
            setStatus(t('Type at least 3 characters.'));
            return;
        }

        controller?.abort();
        controller = new AbortController();
        closeResults();
        setStatus(t('Searching...'));

        try {
            const url = new URL('https://nominatim.openstreetmap.org/search');
            url.search = new URLSearchParams({ q: query, format: 'jsonv2', countrycodes: 'id', limit: '5', 'accept-language': document.documentElement.lang || 'id' });
            const response = await fetch(url, { signal: controller.signal, headers: { Accept: 'application/json' } });
            if (!response.ok) throw new Error(response.statusText);
            const places = await response.json();

            if (!places.length) {
                setStatus(t('Address not found. Try a shorter query or pick on the map.'));
                return;
            }

            setStatus('');
            results.innerHTML = places.map((place, index) => `
                <li>
                    <button type="button" class="flex w-full items-start gap-2 px-3 py-2 text-left hover:bg-navy-50 focus:bg-navy-50 focus:outline-none" data-index="${index}">
                        <i class="ti ti-map-pin mt-0.5 text-navy-700" aria-hidden="true"></i>
                        <span class="text-stone-700">${escapeHtml(place.display_name)}</span>
                    </button>
                </li>`).join('');
            results.hidden = false;
            results.onclick = (event) => {
                const button = event.target.closest('button[data-index]');
                if (!button) return;
                const place = places[Number(button.dataset.index)];
                onPick(Number(place.lat), Number(place.lon));
                closeResults();
                setStatus(t('Pin moved. Drag it to fine-tune the position.'));
            };
        } catch (error) {
            if (error.name !== 'AbortError') setStatus(t('Address search failed. Pick the location on the map instead.'));
        }
    };

    box.querySelector('[data-geocode-search]').addEventListener('click', () => search(input.value));
    box.querySelector('[data-geocode-from-address]').addEventListener('click', () => {
        input.value = document.getElementById('address').value;
        search(input.value);
    });
    input.addEventListener('keydown', (event) => {
        if (event.key === 'Enter') {
            event.preventDefault(); // jangan kirim form penjahit
            search(input.value);
        } else if (event.key === 'Escape') {
            closeResults();
        }
    });
    document.addEventListener('click', (event) => {
        if (!box.contains(event.target)) closeResults();
    });
}

function escapeHtml(value) {
    return String(value).replace(/[&<>"']/g, (char) => ({ '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' })[char]);
}
