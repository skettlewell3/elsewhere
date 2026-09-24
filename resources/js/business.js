import '../css/business.css';

document.addEventListener('DOMContentLoaded', () => {
    const panels = document.querySelectorAll(
        '[data-business-location-panel]'
    );

    panels.forEach((panel) => {
        const toggle = panel.querySelector(
            '[data-business-location-toggle]'
        );

        if (!toggle) {
            return;
        }

        const primaryView =
            toggle.dataset.primaryView;

        const secondaryView =
            toggle.dataset.secondaryView;

        const views = panel.querySelectorAll(
            '[data-business-location-view]'
        );

        const showView = (viewName) => {
            views.forEach((view) => {
                view.hidden =
                    view.dataset.businessLocationView !==
                    viewName;
            });

            panel.dataset.activeView = viewName;

            toggle.textContent =
                viewName === 'contact'
                    ? 'View location'
                    : 'View contact details';
        };

        toggle.addEventListener('click', () => {
            const activeView =
                panel.dataset.activeView;

            showView(
                activeView === primaryView
                    ? secondaryView
                    : primaryView
            );
        });
    });
});