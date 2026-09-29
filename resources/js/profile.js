/**
 * Halaman profil: tab (Ulasan saya / Tersimpan / Pengaturan), pratinjau foto profil,
 * dan daftar penjahit tersimpan dari localStorage.
 */
import { getSavedIds, onSavedChange, toggleSaved } from './lib/saved';

const t = window.t;
const escapeHtml = window.escapeHtml;

/*
| Tab (sinkron dengan hash URL, misalnya /profile#pengaturan setelah menyimpan form)
*/
const TABS = ['ulasan', 'tersimpan', 'pengaturan'];
const tabButtons = document.querySelectorAll('[data-tab]');
const panels = document.querySelectorAll('[data-panel]');

function showTab(name, { updateHash = false } = {}) {
    tabButtons.forEach((button) => {
        const active = button.dataset.tab === name;
        button.classList.toggle('is-active', active);
        button.setAttribute('aria-selected', String(active));
        button.tabIndex = active ? 0 : -1;
    });
    panels.forEach((panel) => {
        panel.hidden = panel.dataset.panel !== name;
    });

    if (updateHash) {
        history.replaceState(null, '', `#${name}`);
    }
}

tabButtons.forEach((button) => {
    button.addEventListener('click', () => showTab(button.dataset.tab, { updateHash: true }));
});

// Navigasi tab dengan tombol panah (pola ARIA tabs)
document.querySelector('[role="tablist"]')?.addEventListener('keydown', (event) => {
    if (!['ArrowLeft', 'ArrowRight'].includes(event.key)) return;
    const current = TABS.indexOf(document.querySelector('[data-tab].is-active')?.dataset.tab);
    const next = TABS[(current + (event.key === 'ArrowRight' ? 1 : TABS.length - 1)) % TABS.length];
    showTab(next, { updateHash: true });
    document.querySelector(`[data-tab="${next}"]`).focus();
});

const initialTab = window.location.hash.slice(1);
showTab(TABS.includes(initialTab) ? initialTab : 'ulasan');

window.addEventListener('hashchange', () => {
    const tab = window.location.hash.slice(1);
    if (TABS.includes(tab)) showTab(tab);
});

/*
| Pratinjau foto profil sebelum disimpan
*/
const avatarInput = document.getElementById('profile_picture');
const avatarPreview = document.getElementById('avatar-preview');
const avatarPlaceholder = document.getElementById('avatar-placeholder');

avatarInput?.addEventListener('change', () => {
    const file = avatarInput.files?.[0];
    if (!file) return;

    avatarPreview.src = URL.createObjectURL(file);
    avatarPreview.classList.remove('hidden');
    avatarPlaceholder.classList.add('hidden');
});

/*
| Penjahit tersimpan
*/
const savedList = document.querySelector('[data-saved-list]');
const savedEmpty = document.querySelector('[data-saved-empty]');
const savedCounts = document.querySelectorAll('[data-saved-count]');
let allTailors = null;

function savedCard(tailor) {
    const url = `${savedList.dataset.detailBase}/${encodeURIComponent(tailor.slug)}`;
    const rating = tailor.rating !== null ? `<span class="rating-star">★ ${Number(tailor.rating).toFixed(1)}</span> · ` : '';

    return `<article class="card flex gap-3 p-3">
        <a href="${escapeHtml(url)}" class="shrink-0">
            <img src="${escapeHtml(tailor.cover_url)}" alt="" loading="lazy" class="h-20 w-20 rounded-lg bg-navy-50 object-cover">
        </a>
        <div class="min-w-0 flex-1">
            <a href="${escapeHtml(url)}" class="block truncate font-semibold text-stone-900 hover:text-navy-700">${escapeHtml(tailor.name)}</a>
            <p class="mt-0.5 truncate text-xs text-stone-500">${rating}${escapeHtml(tailor.address)}</p>
            <button type="button" data-unsave="${Number(tailor.id)}" class="mt-2 inline-flex items-center gap-1 text-xs font-semibold text-red-600 hover:underline">
                <i class="ti ti-bookmark-off" aria-hidden="true"></i>${escapeHtml(t('Remove'))}
            </button>
        </div>
    </article>`;
}

async function renderSaved() {
    const ids = getSavedIds();
    savedCounts.forEach((el) => {
        el.textContent = ids.length;
    });

    if (!savedList) return;

    if (ids.length === 0) {
        savedList.innerHTML = '';
        savedEmpty.hidden = false;
        return;
    }

    savedEmpty.hidden = true;

    try {
        allTailors ??= await (await fetch(savedList.dataset.source, { headers: { Accept: 'application/json' } })).json();
    } catch {
        savedList.innerHTML = `<p class="text-sm text-red-600">${escapeHtml(t('Failed to load details. Please try again.'))}</p>`;
        return;
    }

    const saved = allTailors.filter((tailor) => ids.includes(Number(tailor.id)));
    savedList.innerHTML = saved.map(savedCard).join('');
    savedEmpty.hidden = saved.length > 0;
}

savedList?.addEventListener('click', (event) => {
    const button = event.target.closest('[data-unsave]');
    if (button) toggleSaved(button.dataset.unsave);
});

onSavedChange(renderSaved);
renderSaved();
