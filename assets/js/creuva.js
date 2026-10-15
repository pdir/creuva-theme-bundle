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

/**
 * Style switcher module (front end preview of the color schemes). The inline
 * script of the module applies the stored choice and provides the state.
 */
const initStyleSwitcher = () => {
  const switcher = document.querySelector('[data-creuva-style-switcher]');
  const api = window.creuvaStyleSwitcher;

  if (!switcher || !api) {
    return;
  }

  const toggle = switcher.querySelector('[data-creuva-style-toggle]');
  const panel = document.getElementById(toggle.getAttribute('aria-controls'));
  const defaults = JSON.parse(switcher.dataset.creuvaStyleSwitcher);
  const inputs = switcher.querySelectorAll('[data-style-key]');

  const sync = () => {
    const current = { ...defaults, ...api.state };

    inputs.forEach((input) => {
      input.checked = current[input.dataset.styleKey] === input.value;
    });
  };

  const save = () => {
    try {
      localStorage.setItem('creuva-style', JSON.stringify(api.state));
    } catch (e) {
      // Storage not available (e.g. private mode): the choice applies to this page only
    }
  };

  const setOpen = (open) => {
    panel.hidden = !open;
    toggle.setAttribute('aria-expanded', String(open));
    switcher.classList.toggle('is-open', open);
  };

  toggle.addEventListener('click', () => setOpen(panel.hidden));

  document.addEventListener('keydown', (event) => {
    if (event.key === 'Escape' && !panel.hidden) {
      setOpen(false);
      toggle.focus();
    }
  });

  document.addEventListener('click', (event) => {
    if (!panel.hidden && !switcher.contains(event.target)) {
      setOpen(false);
    }
  });

  switcher.addEventListener('change', (event) => {
    const { styleKey } = event.target.dataset;

    if (!styleKey) {
      return;
    }

    api.state[styleKey] = event.target.value;
    api.apply({ [styleKey]: event.target.value });
    save();
  });

  // Back to the settings of the page layout (also removes the URL parameters)
  switcher.querySelector('[data-creuva-style-reset]').addEventListener('click', () => {
    try {
      localStorage.removeItem('creuva-style');
    } catch (e) {
      // Nothing stored
    }

    const url = new URL(location.href);
    Object.keys(api.allowed).forEach((key) => url.searchParams.delete(key));
    location.replace(url);
  });

  // Keep a choice passed via URL while browsing the demo
  if (Object.keys(api.state).length) {
    save();
  }

  sync();
};

document.addEventListener('DOMContentLoaded', () => {
  initStickyHeader();
  initNavigation();
  initStyleSwitcher();
});
