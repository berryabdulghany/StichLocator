/**
 * Penjahit tersimpan (bookmark), disimpan di localStorage browser.
 * Belum butuh login; bisa dipindah ke database/akun di tahap berikutnya.
 */

const STORAGE_KEY = 'stichlocator:saved';
const listeners = new Set();

export function getSavedIds() {
    try {
        const ids = JSON.parse(localStorage.getItem(STORAGE_KEY) ?? '[]');
        return Array.isArray(ids) ? ids.map(Number).filter(Number.isFinite) : [];
    } catch {
        // localStorage bisa tidak tersedia (mode privat, diblokir browser)
        return [];
    }
}

export function isSaved(id) {
    return getSavedIds().includes(Number(id));
}

/** Simpan/hapus penjahit, mengembalikan status baru (true = tersimpan) */
export function toggleSaved(id) {
    const ids = getSavedIds();
    const numericId = Number(id);
    const saved = !ids.includes(numericId);
    const next = saved ? [...ids, numericId] : ids.filter((savedId) => savedId !== numericId);

    try {
        localStorage.setItem(STORAGE_KEY, JSON.stringify(next));
    } catch {
        // Abaikan: status tetap berlaku sampai halaman dimuat ulang
    }

    listeners.forEach((listener) => listener(next));

    return saved;
}

/** Dipanggil setiap daftar tersimpan berubah (termasuk dari tab browser lain) */
export function onSavedChange(listener) {
    listeners.add(listener);
}

window.addEventListener('storage', (event) => {
    if (event.key === STORAGE_KEY) {
        const ids = getSavedIds();
        listeners.forEach((listener) => listener(ids));
    }
});
