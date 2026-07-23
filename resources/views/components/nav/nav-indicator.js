export function setupNavIndicator() {
    const container = document.querySelector('.navbar-links');
    const indicator = document.querySelector('.nav-active-indicator');
    const active = document.querySelector('.navlink.active');

    if (!container || !indicator || !active) return;

    const previous = sessionStorage.getItem('nav-active');
    const previousLink = document.querySelector(`[data-name="${previous}"]`);

    const getOffset = (link) => {
        const containerRect = container.getBoundingClientRect();
        const linkRect = link.getBoundingClientRect();
        const padding = parseFloat(getComputedStyle(container).paddingLeft);

        return linkRect.left - containerRect.left - padding;
    };

    const moveIndicator = (link) => {
        indicator.style.transform = `translateX(${getOffset(link)}px)`;
    };

    if (previousLink) {
        indicator.style.transition = 'none';

        moveIndicator(previousLink);

        requestAnimationFrame(() => {
            indicator.style.transition = 'transform .25s ease';
            moveIndicator(active);
        });
    } else {
        moveIndicator(active);
    }

    sessionStorage.setItem('nav-active', active.dataset.name);

    window.addEventListener('resize', () => {
        moveIndicator(active);
    });
}