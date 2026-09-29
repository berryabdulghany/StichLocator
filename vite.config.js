import { defineConfig } from 'vite';
import laravel from 'laravel-vite-plugin';

export default defineConfig({
    plugins: [
        laravel({
            input: [
                'resources/css/app.css',
                'resources/js/app.js',
                'resources/js/explorer.js',
                'resources/js/penjahit.js',
                'resources/js/profile.js',
                'resources/js/admin.js',
            ],
            refresh: true,
        }),
    ],
    // Pastikan hanya ada satu salinan Leaflet. Plugin markercluster dan maplibre-gl-leaflet
    // menempel ke objek L yang sama; dua salinan membuat window.L tertimpa.
    resolve: {
        dedupe: ['leaflet'],
    },
    build: {
        // Library peta dipisah ke chunk sendiri supaya di-cache browser terpisah dari kode aplikasi.
        // MapLibre memang besar (~1 MB, ~300 KB gzip), jadi batas peringatan dinaikkan.
        chunkSizeWarningLimit: 1300,
        rollupOptions: {
            output: {
                manualChunks: {
                    maplibre: ['maplibre-gl', '@maplibre/maplibre-gl-leaflet'],
                    leaflet: ['leaflet', 'leaflet.markercluster'],
                },
            },
        },
    },
    optimizeDeps: {
        include: ['leaflet', 'leaflet.markercluster', 'maplibre-gl', '@maplibre/maplibre-gl-leaflet'],
        // MapLibre v5 dibutuhkan oleh gaya peta OpenFreeMap
    },
});
