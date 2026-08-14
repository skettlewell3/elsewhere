import {
    Map,
    Marker,
    Popup,
    setWorkerUrl,
} from 'maplibre-gl';
import 'maplibre-gl/dist/maplibre-gl.css';
import '../css/directory.css';
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

//  just in case 
// const TOP_VISIBLE_HEIGHTS = [
//     112,
//     80,
//     50,
//     38,
//     38,
//     28,
//     0
// ];

// DIRECTORY DECK POSITIONING

const deck = document.querySelector('.directoryDeck');
const cards = [...document.querySelectorAll('.directoryCard')];

const CARD_HEIGHT = 140;
const CARD_NAME_HEIGHT = 38;
const CARD_MAX_COVERAGE = CARD_HEIGHT - CARD_NAME_HEIGHT;

const FOCUS_POSITION = 0.50;

const FULL_CARD_GAP = 12;
const COMPRESSION_DISTANCE = 200;
const COMPRESSION_CURVE = 0.2;

function getFocusTop(deckHeight) {
    return (deckHeight * FOCUS_POSITION) - (CARD_HEIGHT / 2);
}

function getVisibleHeight(distance) {
    const progress = Math.min(distance / COMPRESSION_DISTANCE, 1);
    const coverage = CARD_MAX_COVERAGE * Math.pow(progress, COMPRESSION_CURVE);

    return Math.max(
        CARD_HEIGHT - coverage,
        CARD_NAME_HEIGHT
    );
}

function positionCards() {
    if (!deck || !cards.length) return;

    const deckHeight = deck.clientHeight;
    if (!deckHeight) return;

    const focusIndex = Math.floor(cards.length / 2);
    const focusTop = getFocusTop(deckHeight);
    const focusBottom = focusTop + CARD_HEIGHT;

    const positions = cards.map((card, index) => ({
        card,
        index,
        top: 0
    }));

    // FOCUS

    positions[focusIndex].top = focusTop;

    // ABOVE FOCUS

    let top = focusTop - FULL_CARD_GAP - CARD_HEIGHT;

    for (let i = focusIndex - 1; i >= 0; i--) {
        const distance = focusIndex - i;

        positions[i].top = top;

        const visibleHeight = getVisibleHeight(
            (distance + 1) * CARD_NAME_HEIGHT
        );

        top -= visibleHeight;
    }

    // BELOW FOCUS

    let bottom = focusBottom + FULL_CARD_GAP;

    for (let i = focusIndex + 1; i < cards.length; i++) {
        const distance = i - focusIndex;

        positions[i].top = bottom;

        const visibleHeight = getVisibleHeight(
            (distance + 1) * CARD_NAME_HEIGHT
        );

        bottom += visibleHeight;
    }

    // APPLY

    positions.forEach(item => {
        item.card.style.top = `${item.top}px`;
        item.card.style.zIndex = 100 + item.index;
    });
}

positionCards();

window.addEventListener('resize', positionCards);

// END OF DIRECTORY DECK POSITIONING