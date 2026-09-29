// Leaflet sebagai modul, sekaligus dipasang ke window.L karena plugin
// leaflet.markercluster mengandalkan variabel global L.
import L from 'leaflet';
import 'leaflet/dist/leaflet.css';

window.L = L;

export default L;
