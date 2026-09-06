function initialiseDashboardCalendar() {

    const calendar = document.querySelector(
        "[data-dashboard-calendar]"
    );

    if (!calendar) return;

    const view = calendar.querySelector(
        "[data-calendar-view]"
    );

    const previousButton = calendar.querySelector(
        "[data-calendar-previous]"
    );

    const nextButton = calendar.querySelector(
        "[data-calendar-next]"
    );

    let windowOffset = 0;
    let selectedPromotionIndex = 0;

    /*
     * Schedule items represent things already
     * belonging to the user's day.
     */
    const mockSchedule = [
        {
            id: "schedule-1",
            dayOffset: 0,
            start: "10:00",
            end: "11:00",
            type: "appointment",
            title: "Hair appointment",
            meta: "City Barbers"
        },
        {
            id: "schedule-2",
            dayOffset: 0,
            start: "13:00",
            end: "14:00",
            type: "appointment",
            title: "Lunch booking",
            meta: "The King's Arms"
        },
        {
            id: "schedule-3",
            dayOffset: 1,
            start: "18:00",
            end: "19:30",
            type: "appointment",
            title: "Live music",
            meta: "The Anchor"
        },
        {
            id: "schedule-4",
            dayOffset: 5,
            start: "11:00",
            end: "12:00",
            type: "appointment",
            title: "Booking",
            meta: "The Courtyard"
        }
    ];

    /*
     * Promotions/events are discoverable activity
     * from businesses/apps the user follows.
     */
    const mockPromotions = [
        {
            id: "promo-1",
            dayOffset: 0,
            start: "17:00",
            end: "20:00",
            type: "promotion",
            title: "Double EP",
            business: "The King's Arms",
            description: "Earn double EP on eligible purchases.",
            addable: true
        },
        {
            id: "promo-2",
            dayOffset: 0,
            start: "19:30",
            end: "22:00",
            type: "event",
            title: "Live Music",
            business: "The Anchor",
            description: "Live acoustic set from local artists.",
            addable: true
        },
        {
            id: "promo-3",
            dayOffset: 0,
            start: "12:00",
            end: "18:00",
            type: "promotion",
            title: "Clearance Sale",
            business: "Harbour Goods",
            description: "Selected lines reduced for one day only.",
            addable: false
        },
        {
            id: "promo-4",
            dayOffset: 3,
            start: "10:00",
            end: "16:00",
            type: "promotion",
            title: "Triple EP",
            business: "Coffee House",
            description: "Triple EP across selected drinks.",
            addable: true
        },
        {
            id: "promo-5",
            dayOffset: 6,
            start: "19:30",
            end: "20:00",
            type: "event",
            title: "Predictions Close",
            business: "Perfect10",
            description: "Final deadline for this week's predictions.",
            addable: true
        }
    ];

    /*
     * Promotions/events added during this browser session.
     */
    const addedScheduleItems = [];

    const DAY_START_HOUR = 8;
    const DAY_END_HOUR = 24;
    const HOUR_WIDTH = 90;

    function getToday() {

        const now = new Date();

        return new Date(
            now.getFullYear(),
            now.getMonth(),
            now.getDate()
        );
    }

    function addDays(date, amount) {

        const next = new Date(date);

        next.setDate(
            next.getDate() + amount
        );

        return next;
    }

    function sameDay(a, b) {

        return (
            a.getFullYear() === b.getFullYear() &&
            a.getMonth() === b.getMonth() &&
            a.getDate() === b.getDate()
        );
    }

    function getDateKey(date) {

        return [
            date.getFullYear(),
            String(date.getMonth() + 1).padStart(2, "0"),
            String(date.getDate()).padStart(2, "0")
        ].join("-");
    }

    function getDayOffset(date) {

        const today = getToday();

        const millisecondsPerDay =
            1000 * 60 * 60 * 24;

        return Math.round(
            (date - today) / millisecondsPerDay
        );
    }

    function getDayName(date) {

        return new Intl.DateTimeFormat(
            "en-GB",
            {
                weekday: "short"
            }
        ).format(date);
    }

    function getMonthName(date) {

        return new Intl.DateTimeFormat(
            "en-GB",
            {
                month: "short"
            }
        ).format(date);
    }

    function getScheduleForDate(date) {

        const dayOffset =
            getDayOffset(date);

        return [
            ...mockSchedule,
            ...addedScheduleItems
        ].filter(
            (item) =>
                item.dayOffset === dayOffset
        );
    }

    function getPromotionsForDate(date) {

        const dayOffset =
            getDayOffset(date);

        return mockPromotions.filter(
            (item) =>
                item.dayOffset === dayOffset
        );
    }

    function timeToMinutes(time) {

        const [hours, minutes] =
            time.split(":").map(Number);

        return (
            (hours * 60) +
            minutes
        );
    }

    /*
     * WEEK VIEW
     */

    function renderWeek() {

        const today =
            getToday();

        const firstDate =
            addDays(
                today,
                windowOffset
            );

        const days = Array.from(
            { length: 7 },
            (_, index) =>
                addDays(
                    firstDate,
                    index
                )
        );

        view.innerHTML = `
            <div class="calendar-days">

                ${days.map((date) => {

                    const isToday =
                        sameDay(
                            date,
                            today
                        );

                    const schedule =
                        getScheduleForDate(
                            date
                        );

                    const promotions =
                        getPromotionsForDate(
                            date
                        );

                    /*
                     * If a promotion has been added to the
                     * user's calendar, don't also render the
                     * discoverable version in the week preview.
                     */
                    const addedPromotionIds =
                        new Set(
                            schedule
                                .filter(
                                    (item) =>
                                        item.sourcePromotionId
                                )
                                .map(
                                    (item) =>
                                        item.sourcePromotionId
                                )
                        );

                    const visiblePromotions =
                        promotions.filter(
                            (promotion) =>
                                !addedPromotionIds.has(
                                    promotion.id
                                )
                        );

                    /*
                     * Personal schedule items appear as
                     * appointment-style items in week view.
                     *
                     * Discoverable promotions/events use
                     * promotion styling.
                     */
                    const allPreviewItems = [

                        ...schedule.map(
                            (item) => ({
                                time:
                                    item.start,

                                type:
                                    "appointment",

                                title:
                                    item.title,

                                meta:
                                    item.meta
                            })
                        ),

                        ...visiblePromotions.map(
                            (item) => ({
                                time:
                                    item.start,

                                type:
                                    "promotion",

                                title:
                                    item.title,

                                meta:
                                    item.business
                            })
                        )
                    ];

                    /*
                     * Don't overcrowd narrow day columns.
                     */
                    const previewItems =
                        allPreviewItems.slice(
                            0,
                            2
                        );

                    const hiddenItemCount =
                        Math.max(
                            0,
                            allPreviewItems.length -
                            previewItems.length
                        );

                    return `
                        <button
                            class="
                                calendar-day
                                ${
                                    isToday
                                        ? "is-today"
                                        : ""
                                }
                            "
                            type="button"
                            data-calendar-day="${getDateKey(date)}"
                        >

                            <div class="calendar-day-header">

                                <span class="calendar-day-name">
                                    ${
                                        isToday
                                            ? "Today"
                                            : getDayName(date)
                                    }
                                </span>

                                <span class="calendar-day-number">
                                    ${String(date.getDate()).padStart(2, "0")}
                                </span>

                                <span class="calendar-day-month">
                                    ${getMonthName(date)}
                                </span>

                            </div>

                            <div class="calendar-day-content">

                                ${previewItems.map((item) => `
                                    <div
                                        class="
                                            calendar-item
                                            calendar-item-${item.type}
                                        "
                                    >

                                        <span class="calendar-item-time">
                                            ${item.time}
                                        </span>

                                        <span class="calendar-item-title">
                                            ${item.title}
                                        </span>

                                        ${
                                            item.meta
                                                ? `
                                                    <span class="calendar-item-meta">
                                                        ${item.meta}
                                                    </span>
                                                `
                                                : ""
                                        }

                                    </div>
                                `).join("")}

                                ${
                                    hiddenItemCount > 0
                                        ? `
                                            <span class="calendar-item-more">
                                                +${hiddenItemCount} more
                                            </span>
                                        `
                                        : ""
                                }

                            </div>

                        </button>
                    `;
                }).join("")}

            </div>
        `;

        previousButton.disabled =
            windowOffset === 0;

        previousButton.style.visibility =
            "visible";

        nextButton.style.visibility =
            "visible";

        bindDayButtons();
    }

    function bindDayButtons() {

        const buttons =
            view.querySelectorAll(
                "[data-calendar-day]"
            );

        buttons.forEach((button) => {

            button.addEventListener(
                "click",
                () => {

                    const [
                        year,
                        month,
                        day
                    ] = button
                        .dataset
                        .calendarDay
                        .split("-")
                        .map(Number);

                    const date =
                        new Date(
                            year,
                            month - 1,
                            day
                        );

                    renderDay(date);
                }
            );
        });
    }

    /*
     * DAY VIEW
     */

    function renderDay(date) {

        selectedPromotionIndex = 0;

        previousButton.style.visibility =
            "hidden";

        nextButton.style.visibility =
            "hidden";

        renderDayContents(date);
    }

    function renderDayContents(date) {

        const promotions =
            getPromotionsForDate(
                date
            );

        const schedule =
            getScheduleForDate(
                date
            );

        if (
            selectedPromotionIndex >
            promotions.length - 1
        ) {
            selectedPromotionIndex = 0;
        }

        const activePromotion =
            promotions[
                selectedPromotionIndex
            ] ?? null;

        view.innerHTML = `
            <div class="calendar-day-view">

                <div class="calendar-day-overview">

                    <div class="calendar-day-date">

                        <span class="calendar-day-date-name">
                            ${getDayName(date)}
                        </span>

                        <span class="calendar-day-date-number">
                            ${String(date.getDate()).padStart(2, "0")}
                        </span>

                        <span class="calendar-day-date-month">
                            ${getMonthName(date)}
                        </span>

                        <button
                            class="calendar-day-back"
                            type="button"
                            data-calendar-back
                        >
                            ← 7 days
                        </button>

                    </div>

                    <div class="calendar-day-promotions">

                        ${
                            activePromotion
                                ? renderPromotionCard(
                                    activePromotion,
                                    promotions
                                )
                                : `
                                    <div class="calendar-promotion-empty">
                                        No subscribed business activity today.
                                    </div>
                                `
                        }

                    </div>

                </div>

                ${renderTimeline(
                    schedule
                )}

            </div>
        `;

        bindDayViewControls(
            date
        );
    }

    /*
     * PROMOTION / EVENT CAROUSEL
     */

    function renderPromotionCard(
        promotion,
        promotions
    ) {

        const isAdded =
            addedScheduleItems.some(
                (item) =>
                    item.sourcePromotionId ===
                    promotion.id
            );

        return `
            <div class="calendar-promotion">

                <div class="calendar-promotion-card">

                    <div class="calendar-promotion-header">

                        <div>

                            <span class="calendar-promotion-type">
                                ${promotion.type}
                            </span>

                            <h3 class="calendar-promotion-title">
                                ${promotion.title}
                            </h3>

                        </div>

                        <div class="calendar-promotion-time">
                            ${promotion.start}
                            –
                            ${promotion.end}
                        </div>

                    </div>

                    <div class="calendar-promotion-business">
                        ${promotion.business}
                    </div>

                    <p class="calendar-promotion-description">
                        ${promotion.description}
                    </p>

                    <div class="calendar-promotion-actions">

                        ${
                            promotion.addable
                                ? `
                                    <button
                                        class="
                                            calendar-promotion-add
                                            ${
                                                isAdded
                                                    ? "is-added"
                                                    : ""
                                            }
                                        "
                                        type="button"
                                        data-calendar-toggle-promotion="${promotion.id}"
                                    >
                                        ${
                                            isAdded
                                                ? "− Remove from calendar"
                                                : "+ Add to calendar"
                                        }
                                    </button>
                                `
                                : ""
                        }

                        <a
                            class="calendar-promotion-view"
                            href="#"
                        >
                            View
                        </a>

                    </div>

                </div>

                ${
                    promotions.length > 1
                        ? `
                            <div class="calendar-promotion-navigation">

                                <button
                                    class="calendar-promotion-arrow"
                                    type="button"
                                    data-calendar-promotion-previous
                                    aria-label="Previous promotion"
                                >
                                    ‹
                                </button>

                                <div class="calendar-promotion-dots">

                                    ${promotions.map(
                                        (_, index) => `
                                            <button
                                                class="
                                                    calendar-promotion-dot
                                                    ${
                                                        index ===
                                                        selectedPromotionIndex
                                                            ? "is-active"
                                                            : ""
                                                    }
                                                "
                                                type="button"
                                                data-calendar-promotion-index="${index}"
                                                aria-label="Show item ${index + 1}"
                                            ></button>
                                        `
                                    ).join("")}

                                </div>

                                <button
                                    class="calendar-promotion-arrow"
                                    type="button"
                                    data-calendar-promotion-next
                                    aria-label="Next promotion"
                                >
                                    ›
                                </button>

                            </div>
                        `
                        : ""
                }

            </div>
        `;
    }

    /*
     * TIMELINE
     *
     * Added promotions/events occupy a slim upper lane.
     * Appointments occupy the larger lane underneath.
     */

    function renderTimeline(schedule) {

        const totalHours =
            DAY_END_HOUR -
            DAY_START_HOUR;

        const trackWidth =
            totalHours *
            HOUR_WIDTH;

        const timelinePromotions =
            schedule.filter(
                (item) =>
                    item.sourcePromotionId
            );

        const timelineAppointments =
            schedule.filter(
                (item) =>
                    !item.sourcePromotionId
            );

        return `
            <div class="calendar-timeline">

                <div
                    class="calendar-timeline-track"
                    style="width: ${trackWidth}px"
                >

                    <div class="calendar-timeline-hours">

                        ${Array.from(
                            {
                                length:
                                    totalHours + 1
                            },
                            (_, index) => {

                                const hour =
                                    DAY_START_HOUR +
                                    index;

                                return `
                                    <div
                                        class="calendar-timeline-hour"
                                        style="
                                            left:
                                            ${
                                                index *
                                                HOUR_WIDTH
                                            }px
                                        "
                                    >
                                        <span>
                                            ${String(hour).padStart(2, "0")}:00
                                        </span>
                                    </div>
                                `;
                            }
                        ).join("")}

                    </div>

                    <div class="calendar-timeline-promotions">

                        ${timelinePromotions.map(
                            (item) =>
                                renderTimelineItem(
                                    item,
                                    "promotion"
                                )
                        ).join("")}

                    </div>

                    <div class="calendar-timeline-appointments">

                        ${timelineAppointments.map(
                            (item) =>
                                renderTimelineItem(
                                    item,
                                    "appointment"
                                )
                        ).join("")}

                    </div>

                </div>

            </div>
        `;
    }

    function renderTimelineItem(
        item,
        lane
    ) {

        const dayStartMinutes =
            DAY_START_HOUR * 60
        ;

        const startMinutes =
            timeToMinutes(
                item.start
            )
        ;

        const endMinutes =
            timeToMinutes(
                item.end
            )
        ;

        const ITEM_GAP = 4;

        const rawWidth =
            (
                endMinutes -
                startMinutes
            ) / 60 *
            HOUR_WIDTH
        ;
        
        const left =
            (
                startMinutes -
                dayStartMinutes
            ) / 60 *
            HOUR_WIDTH +
            ITEM_GAP
        ;
        
        const width =
            Math.max(
                rawWidth,
                60
            ) - (ITEM_GAP * 2)
        ;

        return `
            <div
                class="
                    calendar-timeline-item
                    calendar-timeline-item-${lane}
                "
                style="
                    left: ${left}px;
                    width: ${Math.max(width, 60)}px;
                "
            >

                ${
                    lane === "promotion"
                        ? `
                            <span class="calendar-timeline-item-title">
                                ${item.title}
                            </span>
                        `
                        : `
                            <span class="calendar-timeline-item-time">
                                ${item.start}–${item.end}
                            </span>

                            <span class="calendar-timeline-item-title">
                                ${item.title}
                            </span>

                            ${
                                item.meta
                                    ? `
                                        <span class="calendar-timeline-item-meta">
                                            ${item.meta}
                                        </span>
                                    `
                                    : ""
                            }
                        `
                }

            </div>
        `;
    }

    /*
     * DAY VIEW CONTROLS
     */

    function bindDayViewControls(date) {

        view.querySelector(
            "[data-calendar-back]"
        )?.addEventListener(
            "click",
            renderWeek
        );

        view.querySelector(
            "[data-calendar-promotion-previous]"
        )?.addEventListener(
            "click",
            () => {

                const promotions =
                    getPromotionsForDate(
                        date
                    );

                if (!promotions.length) {
                    return;
                }

                selectedPromotionIndex =
                    (
                        selectedPromotionIndex -
                        1 +
                        promotions.length
                    ) %
                    promotions.length;

                renderDayContents(
                    date
                );
            }
        );

        view.querySelector(
            "[data-calendar-promotion-next]"
        )?.addEventListener(
            "click",
            () => {

                const promotions =
                    getPromotionsForDate(
                        date
                    );

                if (!promotions.length) {
                    return;
                }

                selectedPromotionIndex =
                    (
                        selectedPromotionIndex +
                        1
                    ) %
                    promotions.length;

                renderDayContents(
                    date
                );
            }
        );

        view.querySelectorAll(
            "[data-calendar-promotion-index]"
        ).forEach((button) => {

            button.addEventListener(
                "click",
                () => {

                    selectedPromotionIndex =
                        Number(
                            button
                                .dataset
                                .calendarPromotionIndex
                        );

                    renderDayContents(
                        date
                    );
                }
            );
        });

        /*
         * Add/remove discoverable promotion/event
         * from the user's calendar.
         */
        view.querySelector(
            "[data-calendar-toggle-promotion]"
        )?.addEventListener(
            "click",
            (event) => {

                const promotionId =
                    event.currentTarget
                        .dataset
                        .calendarTogglePromotion;

                const promotion =
                    mockPromotions.find(
                        (item) =>
                            item.id ===
                            promotionId
                    );

                if (!promotion) {
                    return;
                }

                const existingIndex =
                    addedScheduleItems.findIndex(
                        (item) =>
                            item.sourcePromotionId ===
                            promotion.id
                    );

                /*
                 * Already present:
                 * remove it.
                 */
                if (existingIndex !== -1) {

                    addedScheduleItems.splice(
                        existingIndex,
                        1
                    );

                /*
                 * Not present:
                 * add it.
                 */
                } else {

                    addedScheduleItems.push({
                        id:
                            `added-${promotion.id}`,

                        sourcePromotionId:
                            promotion.id,

                        dayOffset:
                            promotion.dayOffset,

                        start:
                            promotion.start,

                        end:
                            promotion.end,

                        type:
                            promotion.type,

                        title:
                            promotion.title,

                        meta:
                            promotion.business
                    });
                }

                renderDayContents(
                    date
                );
            }
        );
    }

    /*
     * OUTER CALENDAR NAVIGATION
     */

    previousButton.addEventListener(
        "click",
        () => {

            windowOffset =
                Math.max(
                    0,
                    windowOffset - 1
                );

            renderWeek();
        }
    );

    nextButton.addEventListener(
        "click",
        () => {

            windowOffset += 1;

            renderWeek();
        }
    );

    renderWeek();
}

initialiseDashboardCalendar();

// Initialise Side Panel

function initialiseDashboardSidePanels() {

    const side =
        document.querySelector(
            ".dashboard-side"
        );

    if (!side) return;

    const panels = [
        side.querySelector(".wallet"),
        side.querySelector(".verification")
    ].filter(Boolean);

    if (panels.length < 2) {
        return;
    }

    const rowLayout =
        window.matchMedia(
            "(max-width: 1000px)"
        );

    let syncing = false;

    function handleToggle(event) {

        if (syncing) return;

        const changedPanel =
            event.currentTarget;

        syncing = true;

        /*
         * Side-by-side:
         * all panels share the same open state.
         */
        if (rowLayout.matches) {

            panels.forEach((panel) => {
                panel.open =
                    changedPanel.open;
            });

        /*
         * Stacked:
         * only one panel may be open.
         */
        } else if (changedPanel.open) {

            panels.forEach((panel) => {

                if (panel !== changedPanel) {
                    panel.open = false;
                }
            });
        }

        syncing = false;
    }

    panels.forEach((panel) => {
        panel.addEventListener(
            "toggle",
            handleToggle
        );
    });

    /*
     * Resolve state when crossing the breakpoint.
     */
    rowLayout.addEventListener(
        "change",
        (event) => {

            syncing = true;

            if (event.matches) {

                const shouldOpen =
                    panels.some(
                        (panel) =>
                            panel.open
                    );

                panels.forEach((panel) => {
                    panel.open =
                        shouldOpen;
                });

            } else {

                /*
                 * If both were open in row mode,
                 * retain the first one only.
                 */
                const openPanels =
                    panels.filter(
                        (panel) =>
                            panel.open
                    );

                openPanels
                    .slice(1)
                    .forEach((panel) => {
                        panel.open = false;
                    });
            }

            syncing = false;
        }
    );
}

initialiseDashboardSidePanels();

// Initialise Verification Accitivty 

function initialiseVerificationActivity() {

    const components =
        document.querySelectorAll(
            ".verification"
        );

    components.forEach((component) => {

        const pages =
            component.querySelectorAll(
                "[data-verification-page]"
            );

        const buttons =
            component.querySelectorAll(
                "[data-verification-page-button]"
            );

        if (!pages.length || !buttons.length) {
            return;
        }

        function showPage(index) {

            pages.forEach((page) => {

                page.classList.toggle(
                    "is-active",
                    Number(
                        page.dataset
                            .verificationPage
                    ) === index
                );
            });

            buttons.forEach((button) => {

                button.classList.toggle(
                    "is-active",
                    Number(
                        button.dataset
                            .verificationPageButton
                    ) === index
                );
            });
        }

        buttons.forEach((button) => {

            button.addEventListener(
                "click",
                () => {

                    showPage(
                        Number(
                            button.dataset
                                .verificationPageButton
                        )
                    );
                }
            );
        });
    });
}

initialiseVerificationActivity();


// FAVOURITES GALLERY

function initialiseFavouritesGalleries() {

    const galleries =
        document.querySelectorAll(
            "[data-favourites-gallery]"
        );

    galleries.forEach((gallery) => {

        const viewport =
            gallery.querySelector(
                ".favourites-viewport"
            );

        const track =
            gallery.querySelector(
                "[data-favourites-track]"
            );

        const previousButton =
            gallery.querySelector(
                "[data-favourites-previous]"
            );

        const nextButton =
            gallery.querySelector(
                "[data-favourites-next]"
            );

        if (
            !viewport ||
            !track ||
            !previousButton ||
            !nextButton
        ) {
            return;
        }

        function getScrollAmount() {

            const item =
                track.querySelector(
                    ".favourites-item"
                );

            if (!item) {
                return 0;
            }

            const trackStyles =
                getComputedStyle(track);

            const gap =
                parseFloat(
                    trackStyles.columnGap ||
                    trackStyles.gap ||
                    0
                );

            return (
                item.getBoundingClientRect().width +
                gap
            );
        }

        function updateNavigation() {

            const maxScroll =
                viewport.scrollWidth -
                viewport.clientWidth;

            previousButton.disabled =
                viewport.scrollLeft <= 1;

            nextButton.disabled =
                viewport.scrollLeft >=
                maxScroll - 1;

            /*
             * If everything fits, keep the controls
             * visually present but disabled.
             */
            if (maxScroll <= 1) {
                previousButton.disabled = true;
                nextButton.disabled = true;
            }
        }

        previousButton.addEventListener(
            "click",
            () => {

                viewport.scrollBy({
                    left:
                        -getScrollAmount(),
                    behavior:
                        "smooth"
                });
            }
        );

        nextButton.addEventListener(
            "click",
            () => {

                viewport.scrollBy({
                    left:
                        getScrollAmount(),
                    behavior:
                        "smooth"
                });
            }
        );

        viewport.addEventListener(
            "scroll",
            updateNavigation,
            {
                passive: true
            }
        );

        window.addEventListener(
            "resize",
            updateNavigation
        );

        updateNavigation();
    });
}

initialiseFavouritesGalleries();