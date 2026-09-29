/**
 * Utilitas jarak & waktu tempuh.
 */

const EARTH_RADIUS_M = 6371000;
const toRad = (deg) => (deg * Math.PI) / 180;

/** Jarak garis lurus antara dua titik (meter), rumus haversine */
export function distanceMeters(a, b) {
    const dLat = toRad(b.lat - a.lat);
    const dLng = toRad(b.lng - a.lng);
    const h = Math.sin(dLat / 2) ** 2 + Math.cos(toRad(a.lat)) * Math.cos(toRad(b.lat)) * Math.sin(dLng / 2) ** 2;

    return 2 * EARTH_RADIUS_M * Math.asin(Math.sqrt(h));
}

/** "850 m", "1,2 km" (ID) / "1.2 km" (EN) */
export function formatDistance(meters, locale = window.APP_LOCALE) {
    if (meters < 1000) {
        return `${Math.round(meters / 10) * 10} m`;
    }

    const km = meters / 1000;
    const formatted = new Intl.NumberFormat(locale, { maximumFractionDigits: km < 10 ? 1 : 0 }).format(km);

    return `${formatted} km`;
}

/** "12 menit", "1 j 5 mnt" (ID) / "12 min", "1 h 5 min" (EN) */
export function formatDuration(seconds, t = window.t) {
    const minutes = Math.max(1, Math.round(seconds / 60));

    if (minutes < 60) {
        return t(':min min', { min: minutes });
    }

    return t(':h h :min min', { h: Math.floor(minutes / 60), min: minutes % 60 });
}

/**
 * Titik di tepi lingkaran pada arah (bearing) tertentu, untuk menaruh label radius.
 * Pendekatan datar cukup akurat untuk radius beberapa kilometer.
 */
export function pointOnCircle(center, radiusMeters, bearingDeg) {
    const bearing = toRad(bearingDeg);
    const dLat = (radiusMeters * Math.cos(bearing)) / 111320;
    const dLng = (radiusMeters * Math.sin(bearing)) / (111320 * Math.cos(toRad(center.lat)));

    return { lat: center.lat + dLat, lng: center.lng + dLng };
}
