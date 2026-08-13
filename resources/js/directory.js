import {
    Map,
    Marker,
    Popup,
    setWorkerUrl,
} from 'maplibre-gl';
import 'maplibre-gl/dist/maplibre-gl.css';

import workerUrl from 'maplibre-gl/dist/maplibre-gl-worker.mjs?worker&url';

setWorkerUrl(workerUrl);

const businesses = window.directoryBusinesses ?? [];

console.log('Directory businesses:', businesses);

const map = new Map({
    container: 'directory-map',
    style: 'https://tiles.openfreemap.org/styles/liberty',
    center: [-1.4044, 50.8985],
    zoom: 12,
    minZoom: 7,
    maxZoom: 18,
});

businesses.forEach((business) => {

    if (
        business.latitude === null ||
        business.longitude === null
    ) {
        return;
    }

    new Marker()
        .setLngLat([
            Number(business.longitude),
            Number(business.latitude),
        ])
        .setPopup(
            new Popup().setHTML(`
                <strong>${business.name}</strong>
                <p>${business.description}</p>
            `)
        )
        .addTo(map);

});