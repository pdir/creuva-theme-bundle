import '../scss/creuva.scss';

/**
 * Adds "is-scrolled" to the sticky header as soon as the page is scrolled.
 */
const initStickyHeader = () => {
  const header = document.querySelector('[data-creuva-header]');

  if (!header) {
    return;
  }

  const sentinel = document.createElement('div');
  sentinel.setAttribute('aria-hidden', 'true');
  header.before(sentinel);

  new IntersectionObserver(([entry]) => {
    header.classList.toggle('is-scrolled', !entry.isIntersecting);
  }).observe(sentinel);
};

/**
 * Mobile navigation of the header module.
 */
const initNavigation = () => {
  document.querySelectorAll('[data-creuva-nav-toggle]').forEach((toggle) => {
    const header = toggle.closest('.c-header');
    const panel = document.getElementById(toggle.getAttribute('aria-controls'));

    if (!header || !panel) {
      return;
    }

    const setOpen = (open) => {
      header.classList.toggle('is-open', open);
      toggle.setAttribute('aria-expanded', String(open));
      document.documentElement.classList.toggle('has-open-nav', open);
    };

    toggle.addEventListener('click', () => setOpen(!header.classList.contains('is-open')));

    // Close on Escape and when following an anchor on the same page
    document.addEventListener('keydown', (event) => {
      if (event.key === 'Escape' && header.classList.contains('is-open')) {
        setOpen(false);
        toggle.focus();
      }
    });

    panel.addEventListener('click', (event) => {
      if (event.target.closest('a[href*="#"]')) {
        setOpen(false);
      }
    });

    // Reset when switching to the desktop layout
    matchMedia('(min-width: 64rem)').addEventListener('change', (event) => {
      if (event.matches) {
        setOpen(false);
      }
    });
  });
};

document.addEventListener('DOMContentLoaded', () => {
  initStickyHeader();
  initNavigation();
});
