/**
 * Catat klik tombol WhatsApp / rute untuk statistik mitra penjahit.
 *
 * Elemen cukup diberi atribut:
 *   data-track="whatsapp|route" data-track-url="/penjahit/12/klik"
 * Dikirim lewat navigator.sendBeacon supaya tetap terkirim walaupun halaman
 * langsung berpindah ke WhatsApp / Google Maps.
 */
let installed = false;

export function initTracking() {
    if (installed) return;
    installed = true;

    document.addEventListener('click', (event) => {
        const element = event.target.closest('[data-track][data-track-url]');
        if (!element) return;

        const body = new FormData();
        body.append('metric', element.dataset.track);
        body.append('_token', document.querySelector('meta[name="csrf-token"]')?.content ?? '');

        if (navigator.sendBeacon) {
            navigator.sendBeacon(element.dataset.trackUrl, body);
        } else {
            fetch(element.dataset.trackUrl, { method: 'POST', body, keepalive: true }).catch(() => {});
        }
    }, { capture: true });
}
