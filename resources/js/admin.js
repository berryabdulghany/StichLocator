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
| Form penjahit
*/
const tailorForm = document.getElementById('tailor-form');

if (tailorForm) {
    initHours();
    initServices();
    initCoverPreview();
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
}
