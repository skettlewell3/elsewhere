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


// MAP

const map = new Map({
    container: 'directory-map',
    style: 'https://tiles.openfreemap.org/styles/liberty',
    center: [-1.4044, 50.8985],
    zoom: 12,
    minZoom: 7,
    maxZoom: 18,
});


const businessMarkers = new globalThis.Map();


businesses.forEach((business) => {

    if (
        business.latitude === null ||
        business.longitude === null
    ) {
        return;
    }

    const marker = new Marker()
        .setLngLat([
            Number(business.longitude),
            Number(business.latitude),
        ])
        .setPopup(
            new Popup({
                offset: 25,
            }).setHTML(`
                <strong>${business.name}</strong>
                <p>${business.description ?? ''}</p>
            `)
        )
        .addTo(map);

    businessMarkers.set(
        String(business.id),
        marker
    );

});

// END OF MAP


// DIRECTORY PANEL

const directory = document.querySelector(
    '[data-directory]'
);

const directoryPanel = document.querySelector(
    '[data-directory-panel]'
);

const panelUp = document.querySelector(
    '[data-directory-panel-up]'
);

const panelDown = document.querySelector(
    '[data-directory-panel-down]'
);

const mobileDirectoryMedia =
    window.matchMedia('(max-width: 480px)');


const PANEL_STATES = [
    'compact',
    'filters',
    'expanded',
];


function getPanelState() {

    return directoryPanel?.dataset.panelState
        ?? 'expanded';

}


function setPanelState(state) {

    if (
        !directory ||
        !directoryPanel ||
        !PANEL_STATES.includes(state)
    ) {
        return;
    }

    directory.dataset.panelState = state;
    directoryPanel.dataset.panelState = state;


    const stateIndex =
        PANEL_STATES.indexOf(state);


    /*
     * Hide the down arrow at the minimum state.
     */

    if (panelDown) {
        panelDown.hidden =
            stateIndex === 0;
    }


    /*
     * Hide the up arrow at the maximum state.
     */

    if (panelUp) {
        panelUp.hidden =
            stateIndex === PANEL_STATES.length - 1;
    }


    /*
     * Both MapLibre and the desktop card deck
     * need to recalculate after panel dimensions
     * have changed.
     */

    window.setTimeout(() => {

        map.resize();

        if (deckMedia.matches) {
            renderScrollPosition(scrollPosition);
        }

    }, 260);

}


function movePanel(direction) {

    const currentState =
        getPanelState();

    const currentIndex =
        PANEL_STATES.indexOf(currentState);

    const nextIndex =
        currentIndex + direction;


    if (
        nextIndex < 0 ||
        nextIndex >= PANEL_STATES.length
    ) {
        return;
    }


    setPanelState(
        PANEL_STATES[nextIndex]
    );

}


panelUp?.addEventListener('click', () => {

    movePanel(1);

});


panelDown?.addEventListener('click', () => {

    movePanel(-1);

});


// END OF DIRECTORY PANEL


// FIND BUSINESS ON MAP

document
    .querySelectorAll('.directoryCardFocus')
    .forEach((button) => {

        button.addEventListener('click', (event) => {

            event.stopPropagation();

            const card =
                button.closest('.directoryCard');

            if (!card) {
                return;
            }


            const businessId =
                String(card.dataset.businessId);

            const marker =
                businessMarkers.get(businessId);

            if (!marker) {
                return;
            }


            /*
             * Return the panel to map-first mode.
             */

            setPanelState('compact');


            /*
             * Wait for the panel/map transition
             * before focusing the map.
             */

            window.setTimeout(() => {

                map.resize();


                const lngLat =
                    marker.getLngLat();


                map.flyTo({
                    center: [
                        lngLat.lng,
                        lngLat.lat,
                    ],
                    zoom: 16,
                    essential: true,
                });


                /*
                 * Explicitly open the marker popup.
                 */

                const popup =
                    marker.getPopup();

                if (popup) {

                    popup
                        .setLngLat(lngLat)
                        .addTo(map);

                }

            }, 280);

        });

    });

// END OF FIND BUSINESS ON MAP


// DIRECTORY CARD EXPANSION

document
    .querySelectorAll('.directoryCardExpand')
    .forEach((button) => {

        button.addEventListener('click', (event) => {

            event.stopPropagation();

            const card =
                button.closest('.directoryCard');

            if (!card) {
                return;
            }


            const isExpanded =
                card.classList.toggle('expanded');


            button.textContent =
                isExpanded ? '−' : '+';


            const businessName =
                card
                    .querySelector('h2')
                    ?.textContent
                    ?.trim()
                ?? 'business';


            button.setAttribute(
                'aria-label',
                `${isExpanded ? 'Collapse' : 'Expand'} ${businessName}`
            );

        });

    });

// END OF DIRECTORY CARD EXPANSION


// DIRECTORY DECK

const deckMedia =
    window.matchMedia('(min-width: 769px)');

const deck =
    document.querySelector('.directoryDeck');

const cards = [
    ...document.querySelectorAll('.directoryCard')
];


// Constants

const CARD_HEIGHT = 140;
const CARD_NAME_HEIGHT = 38;

const CARD_MAX_COVERAGE =
    CARD_HEIGHT - CARD_NAME_HEIGHT;

const FOCUS_POSITION = 0.6;

const FULL_CARD_GAP = 12;

const COMPRESSION_DISTANCE = 200;
const COMPRESSION_CURVE = 0.2;

// END of Constants.


// Geometry

function getFocusTop(deckHeight) {

    return (
        deckHeight * FOCUS_POSITION
    ) - (
        CARD_HEIGHT / 2
    );

}


function getVisibleHeight(distance) {

    const progress =
        Math.min(
            distance / COMPRESSION_DISTANCE,
            1
        );

    const coverage =
        CARD_MAX_COVERAGE * Math.pow(
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


let scrollPosition =
    focusIndex;


function getStaticPositions(focusIndex) {

    if (
        !deck ||
        !cards.length
    ) {
        return [];
    }


    const deckHeight =
        deck.clientHeight;

    if (!deckHeight) {
        return [];
    }


    const focusTop =
        getFocusTop(deckHeight);

    const focusBottom =
        focusTop + CARD_HEIGHT;


    const positions =
        cards.map((card, index) => ({
            card,
            index,
            top: 0,
        }));


    // FOCUS

    positions[focusIndex].top =
        focusTop;


    // ABOVE FOCUS

    let top =
        focusTop -
        FULL_CARD_GAP -
        CARD_HEIGHT;


    for (
        let i = focusIndex - 1;
        i >= 0;
        i--
    ) {

        const distance =
            focusIndex - i;


        positions[i].top =
            top;


        const visibleHeight =
            getVisibleHeight(
                (distance + 1) *
                CARD_NAME_HEIGHT
            );


        top -= visibleHeight;

    }


    // BELOW FOCUS

    let bottom =
        focusBottom +
        FULL_CARD_GAP;


    for (
        let i = focusIndex + 1;
        i < cards.length;
        i++
    ) {

        const distance =
            i - focusIndex;


        positions[i].top =
            bottom;


        const visibleHeight =
            getVisibleHeight(
                (distance + 1) *
                CARD_NAME_HEIGHT
            );


        bottom += visibleHeight;

    }


    return positions;

}


function positionCards(focusIndex) {

    const positions =
        getStaticPositions(focusIndex);


    positions.forEach((item) => {

        item.card.style.top =
            `${item.top}px`;


        const distance =
            Math.abs(
                item.index -
                focusIndex
            );


        item.card.style.zIndex =
            100 - distance;

    });

}


function interpolatePositions(
    currentPositions,
    nextPositions,
    progress
) {

    return currentPositions.map(
        (current, index) => {

            const next =
                nextPositions[index];


            return {
                card: current.card,
                index: current.index,

                top:
                    current.top +
                    (
                        (
                            next.top -
                            current.top
                        ) *
                        progress
                    ),
            };

        }
    );

}


function renderInterpolatedPositions(
    currentPositions,
    nextPositions,
    progress,
    focusIndex
) {

    const positions =
        interpolatePositions(
            currentPositions,
            nextPositions,
            progress
        );


    positions.forEach((item) => {

        item.card.style.top =
            `${item.top}px`;


        const distance =
            Math.abs(
                item.index -
                focusIndex
            );


        item.card.style.zIndex =
            100 - distance;

    });

}


function renderScrollPosition(
    scrollPosition
) {

    if (
        !deckMedia.matches ||
        !deck ||
        !cards.length
    ) {
        return;
    }


    const minFocusIndex =
        getMinFocusIndex();

    const maxFocusIndex =
        getMaxFocusIndex();


    const clampedPosition =
        Math.max(
            minFocusIndex,
            Math.min(
                scrollPosition,
                maxFocusIndex
            )
        );


    const lowerIndex =
        Math.floor(clampedPosition);

    const upperIndex =
        Math.ceil(clampedPosition);

    const progress =
        clampedPosition -
        lowerIndex;


    if (
        lowerIndex === upperIndex
    ) {

        positionCards(lowerIndex);

        return;

    }


    const currentPositions =
        getStaticPositions(
            lowerIndex
        );

    const nextPositions =
        getStaticPositions(
            upperIndex
        );


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

let wheelTimeout = null;


function snapToNearestFocus() {

    if (isSnapping) {
        return;
    }


    const targetFocusIndex =
        Math.max(
            getMinFocusIndex(),
            Math.min(
                Math.round(
                    scrollPosition
                ),
                getMaxFocusIndex()
            )
        );


    const startPosition =
        scrollPosition;

    const endPosition =
        targetFocusIndex;


    const SNAP_DURATION = 220;

    const startTime =
        performance.now();


    isSnapping = true;


    function animateSnap(
        currentTime
    ) {

        const elapsed =
            currentTime -
            startTime;


        const progress =
            Math.min(
                elapsed /
                SNAP_DURATION,
                1
            );


        const easedProgress =
            1 -
            Math.pow(
                1 - progress,
                3
            );


        scrollPosition =
            startPosition +
            (
                (
                    endPosition -
                    startPosition
                ) *
                easedProgress
            );


        renderScrollPosition(
            scrollPosition
        );


        if (progress < 1) {

            snapAnimationFrame =
                requestAnimationFrame(
                    animateSnap
                );

            return;

        }


        scrollPosition =
            endPosition;

        focusIndex =
            targetFocusIndex;


        isSnapping = false;
        snapAnimationFrame = null;


        positionCards(
            focusIndex
        );

    }


    snapAnimationFrame =
        requestAnimationFrame(
            animateSnap
        );

}


function handleWheel(event) {

    if (
        !deck ||
        !deckMedia.matches ||
        cards.length < 2
    ) {
        return;
    }


    event.preventDefault();


    const SCROLL_SENSITIVITY =
        0.0025;


    scrollPosition +=
        event.deltaY *
        SCROLL_SENSITIVITY;


    scrollPosition =
        Math.max(
            getMinFocusIndex(),
            Math.min(
                scrollPosition,
                getMaxFocusIndex()
            )
        );


    renderScrollPosition(
        scrollPosition
    );


    clearTimeout(
        wheelTimeout
    );


    wheelTimeout =
        setTimeout(() => {

            snapToNearestFocus();

        }, 120);

}


// Deck lifecycle

function initialiseDirectoryDeck() {

    if (
        !deck ||
        !deckMedia.matches
    ) {
        return;
    }


    deck.addEventListener(
        'wheel',
        handleWheel,
        {
            passive: false,
        }
    );


    positionCards(
        focusIndex
    );

}


function destroyDirectoryDeck() {

    if (!deck) {
        return;
    }


    deck.removeEventListener(
        'wheel',
        handleWheel
    );


    clearTimeout(
        wheelTimeout
    );

    wheelTimeout = null;


    if (snapAnimationFrame) {

        cancelAnimationFrame(
            snapAnimationFrame
        );

        snapAnimationFrame = null;

    }


    isSnapping = false;


    cards.forEach((card) => {

        card.style.removeProperty(
            'top'
        );

        card.style.removeProperty(
            'z-index'
        );

    });

}


deckMedia.addEventListener(
    'change',
    (event) => {

        if (event.matches) {

            initialiseDirectoryDeck();

        } else {

            destroyDirectoryDeck();

        }

    }
);


window.addEventListener(
    'resize',
    () => {

        map.resize();


        if (
            deckMedia.matches
        ) {

            renderScrollPosition(
                scrollPosition
            );

        }

    }
);


// INITIAL STATE

if (
    mobileDirectoryMedia.matches
) {

    setPanelState('compact');

} else {

    setPanelState('expanded');

}


initialiseDirectoryDeck();

// END OF DIRECTORY DECK