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

  // Back to the saved settings of the website (also removes the URL parameters)
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
  initStyleSwitcherSave(switcher, api, defaults);
};

/**
 * Saves the choice in the website root (front end preview, administrators only).
 */
const initStyleSwitcherSave = (switcher, api, defaults) => {
  const container = switcher.querySelector('[data-creuva-style-save]');

  if (!container) {
    return;
  }

  const config = JSON.parse(container.dataset.creuvaStyleSave);
  const button = container.querySelector('button');
  const status = container.querySelector('[role="status"]');

  button.addEventListener('click', async () => {
    const choice = { ...defaults, ...api.state };
    const body = new FormData();

    body.append('REQUEST_TOKEN', config.token);
    body.append('root', config.root);
    Object.keys(api.allowed).forEach((key) => body.append(key, choice[key]));

    button.disabled = true;
    container.classList.remove('is-saved', 'is-error');

    try {
      const response = await fetch(config.url, {
        method: 'POST',
        body,
        credentials: 'same-origin',
        headers: { 'X-Requested-With': 'XMLHttpRequest', Accept: 'application/json' },
      });

      if (!response.ok || !(await response.json()).success) {
        throw new Error(String(response.status));
      }

      // The saved settings are the defaults now
      Object.assign(defaults, choice);
      Object.keys(api.state).forEach((key) => delete api.state[key]);

      try {
        localStorage.removeItem('creuva-style');
      } catch (e) {
        // Nothing stored
      }

      container.classList.add('is-saved');
      status.textContent = status.dataset.success;
    } catch (e) {
      container.classList.add('is-error');
      status.textContent = status.dataset.error;
    } finally {
      button.disabled = false;
    }
  });
};

const prefersReducedMotion = () => matchMedia('(prefers-reduced-motion: reduce)').matches;

/**
 * Animation setting of the page layout (data-animations on <html>):
 * "auto" (default), "classes" or "none".
 */
const getAnimationMode = () => document.documentElement.dataset.animations || 'auto';

const REVEAL_CLASSES = {
  'u-reveal-up': 'up',
  'u-reveal-down': 'down',
  'u-reveal-left': 'left',
  'u-reveal-right': 'right',
  'u-reveal-zoom': 'zoom',
  'u-reveal-blur': 'blur',
  'u-reveal-fade': 'fade',
};

// Containers whose children appear one after another
const STAGGER_CONTAINERS = '.u-grid-2, .u-grid-3, .u-grid-4, .u-reveal-stagger, .mod_newslist:has(.layout_latest)';

const getRevealType = (element, fallback = null) => {
  const match = Object.keys(REVEAL_CLASSES).find((name) => element.classList.contains(name));

  return match ? REVEAL_CLASSES[match] : fallback;
};

const readTime = (element, property, fallback) => {
  const value = getComputedStyle(element).getPropertyValue(property).trim();
  const number = parseFloat(value);

  if (Number.isNaN(number)) {
    return fallback;
  }

  return value.endsWith('ms') ? number : number * 1000;
};

/**
 * Elements fade in when they scroll into view. Editors control this with the
 * animation setting of the page layout and the CSS classes u-reveal-* (type),
 * u-reveal-stagger (children one after another) and u-reveal-none (no animation).
 */
const initReveal = () => {
  const mode = getAnimationMode();

  if ('none' === mode || prefersReducedMotion() || !('IntersectionObserver' in window)) {
    return;
  }

  const items = new Map();
  const root = document.documentElement;
  const stagger = readTime(root, '--reveal-stagger', 90);

  const add = (element, type, delay = 0) => {
    if (!items.has(element) && !element.closest('.u-reveal-none')) {
      items.set(element, { type, delay });
    }
  };

  const addChildren = (container, type) => {
    [...container.children]
      .filter((child) => !child.matches('script, style, template'))
      .forEach((child, index) => add(child, getRevealType(child, type), Math.min(index, 5) * stagger));
  };

  // Explicit classes first, they win over the automatic animation
  document.querySelectorAll('[class*="u-reveal-"]').forEach((element) => {
    if (element.classList.contains('u-reveal-stagger')) {
      addChildren(element, getRevealType(element, 'up'));
    } else if (element.matches('.c-media') && !getRevealType(element)) {
      add(element, 'split');
    } else if (getRevealType(element)) {
      add(element, getRevealType(element));
    }
  });

  if ('auto' === mode) {
    document.querySelectorAll('.l-main > .mod_article > :not(.c-hero)').forEach((element) => {
      if (element.matches('.c-media')) {
        add(element, 'split');
      } else if (element.matches(STAGGER_CONTAINERS)) {
        addChildren(element, 'up');
      } else {
        add(element, 'up');
      }
    });
  }

  const duration = readTime(root, '--reveal-duration', 800);

  const reveal = (element) => {
    const { delay } = items.get(element);

    element.classList.add('is-revealed');

    // Back to the normal state, so hover transitions work as usual
    setTimeout(() => {
      element.removeAttribute('data-reveal');
      element.classList.remove('is-revealed');
      element.style.removeProperty('--reveal-delay');
    }, delay + duration + 400);
  };

  const observer = new IntersectionObserver(
    (entries) => {
      entries.forEach((entry) => {
        if (entry.isIntersecting) {
          observer.unobserve(entry.target);
          reveal(entry.target);
        }
      });
    },
    { rootMargin: '0px 0px -8% 0px' },
  );

  const fold = window.innerHeight * 0.92;

  items.forEach(({ type, delay }, element) => {
    // Content that is already visible is never hidden
    if (element.getBoundingClientRect().top < fold) {
      return;
    }

    element.dataset.reveal = type;

    if (delay) {
      element.style.setProperty('--reveal-delay', `${delay}ms`);
    }

    observer.observe(element);
  });
};

const escapeHtml = (text) => text.replace(/[&<>"']/g, (char) => `&#${char.charCodeAt(0)};`);

/**
 * Splits a key figure like "120+", "98 %", "1.200" or "4,9" into prefix,
 * number and suffix.
 */
const parseFigure = (text) => {
  const match = text.match(/^(\D*?)(\d(?:[\d.,\s  ]*\d)?)(\D*)$/);

  if (!match) {
    return null;
  }

  const [, prefix, raw, suffix] = match;
  const separators = raw.match(/[.,]/g) || [];
  const last = Math.max(raw.lastIndexOf('.'), raw.lastIndexOf(','));
  const tail = last > -1 ? raw.slice(last + 1) : '';

  // The last separator is a decimal separator if it differs from the others
  // ("1.500,50") or is the only one and not followed by three digits ("4,9")
  const isDecimal = last > -1 && (new Set(separators).size > 1 || (1 === separators.length && 3 !== tail.length));
  const integer = (isDecimal ? raw.slice(0, last) : raw).replace(/[^\d]/g, '');
  const decimals = isDecimal ? tail.length : 0;
  const value = parseFloat(isDecimal ? `${integer}.${tail}` : integer);
  const grouping = /[\s\u00a0\u202f]/.test(raw) || separators.length > (isDecimal ? 1 : 0);

  return Number.isNaN(value) ? null : { prefix, raw, suffix, value, decimals, grouping };
};

/**
 * Key figures (template variant creuva_stats): prefix and suffix are styled as
 * unit and the numbers count up when they scroll into view (not with the CSS
 * class u-no-countup or the animation setting "none").
 */
const initCountUp = () => {
  const terms = [...document.querySelectorAll('.c-stats dt')].filter((term) => 0 === term.children.length);

  if (!terms.length) {
    return;
  }

  const animate = 'none' !== getAnimationMode() && !prefersReducedMotion() && 'IntersectionObserver' in window;
  const locale = document.documentElement.lang || 'de';
  const unit = (text) => (text.trim() ? `<span class="c-stats__unit">${escapeHtml(text.trim())}</span>` : '');

  const run = (term, figure) => {
    const wrapper = term.querySelector('.c-stats__count');
    const output = term.querySelector('[data-count]');
    const format = new Intl.NumberFormat(locale, {
      minimumFractionDigits: figure.decimals,
      maximumFractionDigits: figure.decimals,
      useGrouping: figure.grouping,
    });
    const duration = Math.min(2400, 1200 + figure.value * 4);
    const start = performance.now();

    const step = (now) => {
      const progress = Math.min(1, (now - start) / duration);
      const eased = 1 - (1 - progress) ** 4;

      output.textContent = format.format(figure.value * eased);

      if (progress < 1) {
        requestAnimationFrame(step);
      } else {
        wrapper.classList.remove('is-counting');
      }
    };

    requestAnimationFrame(step);
  };

  const observer = animate
    ? new IntersectionObserver(
        (entries) => {
          entries.forEach((entry) => {
            if (entry.isIntersecting) {
              observer.unobserve(entry.target);
              run(entry.target, entry.target.creuvaFigure);
            }
          });
        },
        { threshold: 0.6 },
      )
    : null;

  terms.forEach((term) => {
    const figure = parseFigure(term.textContent.trim());

    if (!figure) {
      return;
    }

    const value = `${unit(figure.prefix)}${escapeHtml(figure.raw)}${unit(figure.suffix)}`;

    if (!observer || term.closest('.u-no-countup')) {
      term.innerHTML = value;

      return;
    }

    // The final value stays in the layout (and is read by screen readers)
    term.innerHTML = `<span class="c-stats__count is-counting"><span>${value}</span><span aria-hidden="true">${unit(figure.prefix)}<span data-count>0</span>${unit(figure.suffix)}</span></span>`;
    term.creuvaFigure = figure;
    observer.observe(term);
  });
};

document.addEventListener('DOMContentLoaded', () => {
  initStickyHeader();
  initNavigation();
  initStyleSwitcher();
  initReveal();
  initCountUp();
});
