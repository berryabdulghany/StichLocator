@php
    // Kirim terjemahan bahasa aktif ke JavaScript. Kunci terjemahan memakai teks bahasa Inggris,
    // jadi untuk 'en' cukup objek kosong (t() akan mengembalikan kuncinya).
    $jsTranslationFile = lang_path(app()->getLocale() . '.json');
    $jsTranslations = file_exists($jsTranslationFile)
        ? json_decode(file_get_contents($jsTranslationFile), true)
        : [];
@endphp
<script>
    window.APP_LOCALE = @json(app()->getLocale());
    window.I18N = @json((object) $jsTranslations);

    /**
     * Terjemahkan teks di JavaScript, setara dengan __() di Blade.
     * Contoh: t(':count reviews', { count: 3 })
     */
    function t(key, replace = {}) {
        let text = window.I18N[key] ?? key;
        for (const [name, value] of Object.entries(replace)) {
            text = text.replaceAll(':' + name, value);
        }
        return text;
    }

    // Escape teks sebelum dimasukkan ke innerHTML / popup (mencegah XSS)
    function escapeHtml(value) {
        return String(value ?? '')
            .replace(/&/g, '&amp;')
            .replace(/</g, '&lt;')
            .replace(/>/g, '&gt;')
            .replace(/"/g, '&quot;')
            .replace(/'/g, '&#039;');
    }
</script>
