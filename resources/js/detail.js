/**
 * Interaksi di detail penjahit (drawer halaman peta & halaman /penjahit/{slug}):
 * bagikan, lightbox foto, input bintang, dan kirim ulasan.
 */
import { isSaved, toggleSaved } from './lib/saved';

const t = window.t;
const escapeHtml = window.escapeHtml;

/**
 * @param {Element} root
 * @param {{ onReviewSubmitted?: Function, onRoute?: (id: number) => void }} options
 *   onRoute: jika diisi (halaman peta), klik tombol Rute menampilkan pratinjau rute
 *   alih-alih membuka Google Maps.
 */
export function initDetail(root, { onReviewSubmitted, onRoute } = {}) {
    const article = root.querySelector('[data-detail]');
    if (!article) {
        return;
    }

    initShare(article);
    initSave(article);
    initRoute(article, onRoute);
    initLightbox(article);
    initReviewForm(article, onReviewSubmitted);
}

function initSave(article) {
    const button = article.querySelector('[data-save]');
    if (!button) {
        return;
    }

    const paint = (saved) => {
        button.setAttribute('aria-pressed', String(saved));
        button.setAttribute('aria-label', saved ? t('Saved') : t('Save'));
        button.title = saved ? t('Saved') : t('Save');
        button.classList.toggle('text-terra-600', saved);
        button.classList.toggle('border-terra-300', saved);
        button.querySelector('i').className = `ti ${saved ? 'ti-bookmark-filled' : 'ti-bookmark'}`;
    };

    paint(isSaved(button.dataset.id));
    button.addEventListener('click', () => paint(toggleSaved(button.dataset.id)));
}

function initRoute(article, onRoute) {
    const link = article.querySelector('[data-route]');
    if (!link || !onRoute) {
        return;
    }

    link.addEventListener('click', (event) => {
        event.preventDefault();
        onRoute(Number(link.dataset.id));
    });
}

function initShare(article) {
    article.querySelector('[data-share]')?.addEventListener('click', async () => {
        const url = article.dataset.shareUrl;
        const title = article.dataset.shareTitle;

        if (navigator.share) {
            try {
                await navigator.share({ title, text: t('Check out this tailor on StichLocator: :name', { name: title }), url });
            } catch {
                // Pengguna menutup dialog bagikan
            }
            return;
        }

        try {
            await navigator.clipboard.writeText(url);
            alert(t('Link copied to clipboard.'));
        } catch {
            alert(t('Could not copy the link. Copy it manually: :url', { url }));
        }
    });
}

function initLightbox(article) {
    const template = article.querySelector('template[data-lightbox-photos]');
    if (!template) {
        return;
    }

    const photos = JSON.parse(template.innerHTML);
    article.querySelectorAll('[data-lightbox-index]').forEach((button) => {
        button.addEventListener('click', () => openLightbox(photos, Number(button.dataset.lightboxIndex)));
    });
}

function openLightbox(photos, startIndex) {
    let index = startIndex;
    const previousFocus = document.activeElement;
    const navButton = 'absolute top-1/2 flex h-10 w-10 -translate-y-1/2 items-center justify-center rounded-full bg-white/10 text-white hover:bg-white/20';

    const overlay = document.createElement('div');
    overlay.className = 'fixed inset-0 z-[2000] flex flex-col items-center justify-center bg-stone-950/90 p-4';
    overlay.setAttribute('role', 'dialog');
    overlay.setAttribute('aria-modal', 'true');
    overlay.setAttribute('aria-label', t('Photos'));
    overlay.innerHTML = `
        <button type="button" data-close class="absolute right-4 top-4 flex h-10 w-10 items-center justify-center rounded-full bg-white/10 text-white hover:bg-white/20" aria-label="${escapeHtml(t('Close'))}">
            <i class="ti ti-x text-xl" aria-hidden="true"></i>
        </button>
        <button type="button" data-prev class="${navButton} left-4" aria-label="${escapeHtml(t('Previous photo'))}">
            <i class="ti ti-chevron-left text-xl" aria-hidden="true"></i>
        </button>
        <button type="button" data-next class="${navButton} right-4" aria-label="${escapeHtml(t('Next photo'))}">
            <i class="ti ti-chevron-right text-xl" aria-hidden="true"></i>
        </button>
        <img data-image class="max-h-[80vh] max-w-full rounded-lg object-contain" alt="">
        <p data-caption class="mt-3 text-center text-xs text-stone-300"></p>`;

    const image = overlay.querySelector('[data-image]');
    const caption = overlay.querySelector('[data-caption]');

    const show = () => {
        const photo = photos[index];
        image.src = photo.url;
        caption.innerHTML = `${index + 1} / ${photos.length}`
            + (photo.credit ? ` · <a href="${escapeHtml(photo.source)}" target="_blank" rel="noopener" class="underline">${escapeHtml(photo.credit)}</a>` : '');
    };
    const move = (step) => {
        index = (index + step + photos.length) % photos.length;
        show();
    };
    const onKey = (event) => {
        if (event.key === 'Escape') close();
        if (event.key === 'ArrowLeft') move(-1);
        if (event.key === 'ArrowRight') move(1);
    };
    const close = () => {
        overlay.remove();
        document.removeEventListener('keydown', onKey);
        previousFocus?.focus();
    };

    overlay.querySelector('[data-close]').addEventListener('click', close);
    overlay.querySelector('[data-prev]').addEventListener('click', () => move(-1));
    overlay.querySelector('[data-next]').addEventListener('click', () => move(1));
    overlay.addEventListener('click', (event) => event.target === overlay && close());
    document.addEventListener('keydown', onKey);

    if (photos.length < 2) {
        overlay.querySelectorAll('[data-prev], [data-next]').forEach((el) => el.remove());
    }

    document.body.appendChild(overlay);
    show();
    overlay.querySelector('[data-close]').focus();
}

function initReviewForm(article, onReviewSubmitted) {
    const form = article.querySelector('[data-review-form]');
    if (!form) {
        return;
    }

    // Warnai bintang sampai nilai yang dipilih
    const stars = form.querySelector('[data-star-input]');
    const paintStars = () => {
        const value = Number(stars.querySelector('input:checked')?.value ?? 0);
        stars.querySelectorAll('[data-star]').forEach((label) => {
            const on = Number(label.dataset.star) <= value;
            label.classList.toggle('text-terra-500', on);
            label.classList.toggle('text-stone-300', !on);
        });
    };
    stars.addEventListener('change', paintStars);
    paintStars();

    const errorEl = form.querySelector('[data-review-error]');
    const submit = form.querySelector('button[type="submit"]');

    form.addEventListener('submit', async (event) => {
        event.preventDefault();
        errorEl.classList.add('hidden');
        submit.disabled = true;

        try {
            const response = await fetch(form.action, {
                method: 'POST',
                body: new FormData(form),
                headers: {
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                    'X-Requested-With': 'XMLHttpRequest',
                    Accept: 'application/json',
                },
            });
            const data = await response.json().catch(() => ({}));

            if (!response.ok) {
                const firstError = data.errors ? Object.values(data.errors)[0][0] : null;
                throw new Error(firstError || data.message || t('Failed to submit review.'));
            }

            if (onReviewSubmitted) {
                onReviewSubmitted();
            } else {
                window.location.reload();
            }
        } catch (error) {
            errorEl.textContent = error.message;
            errorEl.classList.remove('hidden');
            submit.disabled = false;
        }
    });
}
