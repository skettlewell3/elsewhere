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

// DIRECTORY DECK

const deck = document.querySelector('.directoryDeck');
const cards = [...document.querySelectorAll('.directoryCard')];

// Constants

const CARD_HEIGHT = 140;
const CARD_NAME_HEIGHT = 38;
const CARD_MAX_COVERAGE = CARD_HEIGHT - CARD_NAME_HEIGHT;

const FOCUS_POSITION = 0.6;

const FULL_CARD_GAP = 12;
const COMPRESSION_DISTANCE = 200;
const COMPRESSION_CURVE = 0.2;

// END of Constants.


// Geometry

function getFocusTop(deckHeight) {
    return (deckHeight * FOCUS_POSITION) - (CARD_HEIGHT / 2);
}

function getVisibleHeight(distance) {
    const progress = Math.min(distance / COMPRESSION_DISTANCE, 1);
    const coverage = CARD_MAX_COVERAGE * Math.pow(
        progress,
        COMPRESSION_CURVE
    );

    return Math.max(
        CARD_HEIGHT - coverage,
        CARD_NAME_HEIGHT
    );
}

// END of Geometry.


// Positioning

function getMinFocusIndex() {
    if (cards.length <= 1) {
        return 0;
    }

    if (cards.length === 2) {
        return 0;
    }

    return 1;
}

function getMaxFocusIndex() {
    if (cards.length <= 1) {
        return 0;
    }

    if (cards.length === 2) {
        return 1;
    }

    return cards.length - 2;
}

let focusIndex = Math.max(
    getMinFocusIndex(),
    Math.min(
        Math.floor(cards.length / 2),
        getMaxFocusIndex()
    )
);

let scrollPosition = focusIndex;

function getStaticPositions(focusIndex) {
    if (!deck || !cards.length) return [];

    const deckHeight = deck.clientHeight;

    if (!deckHeight) return [];

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

    return positions;
}


function positionCards(focusIndex) {
    const positions = getStaticPositions(focusIndex);

    positions.forEach(item => {

        item.card.style.top = `${item.top}px`;

        const distance = Math.abs(
            item.index - focusIndex
        );

        item.card.style.zIndex = 100 - distance;

    });
}

function interpolatePositions(
    currentPositions,
    nextPositions,
    progress
) {
    return currentPositions.map((current, index) => {

        const next = nextPositions[index];

        return {
            card: current.card,
            index: current.index,
            top:
                current.top +
                ((next.top - current.top) * progress)
        };
    });
}

function renderInterpolatedPositions(
    currentPositions,
    nextPositions,
    progress,
    focusIndex
) {
    const positions = interpolatePositions(
        currentPositions,
        nextPositions,
        progress
    );

    positions.forEach(item => {

        item.card.style.top = `${item.top}px`;

        const distance = Math.abs(
            item.index - focusIndex
        );

        item.card.style.zIndex = 100 - distance;

    });
}

function renderScrollPosition(scrollPosition) {

    const minFocusIndex = getMinFocusIndex();
    const maxFocusIndex = getMaxFocusIndex();

    const clampedPosition = Math.max(
        minFocusIndex,
        Math.min(
            scrollPosition,
            maxFocusIndex
        )
    );

    const lowerIndex = Math.floor(clampedPosition);
    const upperIndex = Math.ceil(clampedPosition);

    const progress = clampedPosition - lowerIndex;

    if (lowerIndex === upperIndex) {
        positionCards(lowerIndex);
        return;
    }

    const currentPositions = getStaticPositions(lowerIndex);
    const nextPositions = getStaticPositions(upperIndex);

    renderInterpolatedPositions(
        currentPositions,
        nextPositions,
        progress,
        lowerIndex
    );
}

// Scrolling

let isSnapping = false;
let snapAnimationFrame = null;

function snapToNearestFocus() {

    if (isSnapping) return;

    const targetFocusIndex = Math.max(
        getMinFocusIndex(),
        Math.min(
            Math.round(scrollPosition),
            getMaxFocusIndex()
        )
    );

    const startPosition = scrollPosition;
    const endPosition = targetFocusIndex;

    const SNAP_DURATION = 220;

    const startTime = performance.now();

    isSnapping = true;

    function animateSnap(currentTime) {

        const elapsed = currentTime - startTime;

        const progress = Math.min(
            elapsed / SNAP_DURATION,
            1
        );

        const easedProgress =
            1 - Math.pow(1 - progress, 3);

        scrollPosition =
            startPosition +
            ((endPosition - startPosition) * easedProgress);

        renderScrollPosition(scrollPosition);

        if (progress < 1) {

            snapAnimationFrame =
                requestAnimationFrame(animateSnap);

            return;
        }

        scrollPosition = endPosition;
        focusIndex = targetFocusIndex;

        isSnapping = false;
        snapAnimationFrame = null;

        positionCards(focusIndex);
    }

    snapAnimationFrame =
        requestAnimationFrame(animateSnap);
}


let wheelTimeout = null;

function handleWheel(event) {

    if (!deck || cards.length < 2) return;
    // if (isSnapping) return; // remove if it fights ability to scroll

    event.preventDefault();

    const SCROLL_SENSITIVITY = 0.0025;

    scrollPosition += event.deltaY * SCROLL_SENSITIVITY;

    scrollPosition = Math.max(
        getMinFocusIndex(),
        Math.min(
            scrollPosition,
            getMaxFocusIndex()
        )
    );

    renderScrollPosition(scrollPosition);

    clearTimeout(wheelTimeout);

    wheelTimeout = setTimeout(() => {
        snapToNearestFocus();
    }, 120);
}


deck.addEventListener('wheel', handleWheel, {
    passive: false
});

// END of Scrolling.

// END of Positioning.

// Initialisation

positionCards(focusIndex);

window.addEventListener('resize', () => {
    renderScrollPosition(scrollPosition);
});

// END of Initialisation.

// END OF DIRECTORY DECK