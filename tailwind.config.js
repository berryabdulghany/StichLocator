import defaultTheme from 'tailwindcss/defaultTheme';
import scrollbarHide from 'tailwind-scrollbar-hide';

/** @type {import('tailwindcss').Config} */
export default {
    content: [
        './resources/**/*.blade.php',
        './resources/**/*.js',
        './vendor/laravel/framework/src/Illuminate/Pagination/resources/views/*.blade.php',
        './storage/framework/views/*.php',
    ],
    theme: {
        extend: {
            fontFamily: {
                sans: ['"Plus Jakarta Sans"', ...defaultTheme.fontFamily.sans],
            },
            colors: {
                // Warna utama StichLocator: biru navy (logo, link, marker terpilih, elemen aktif)
                navy: {
                    50: '#F2F6FB',
                    100: '#E3ECF7',
                    200: '#C3D6EE',
                    300: '#93B5DE',
                    400: '#5B8DC7',
                    500: '#2F6BAE',
                    600: '#185FA5',
                    700: '#0C447C',
                    800: '#0A3663',
                    900: '#072849',
                    950: '#041A30',
                },
                // Warna pendamping: terracotta (tombol cari, rating, tag layanan, CTA WhatsApp)
                terra: {
                    50: '#FDF4F0',
                    100: '#FAE6DD',
                    200: '#F5C9B6',
                    300: '#EEA283',
                    400: '#E57A52',
                    500: '#D85A30',
                    600: '#BF4A23',
                    700: '#993C1D',
                    800: '#712B13',
                    900: '#4A1B0C',
                },
            },
            borderColor: {
                // Garis jahitan (motif putus-putus khas StichLocator)
                stitch: '#B4B2A9',
            },
        },
    },
    plugins: [
        scrollbarHide,
    ],
};
