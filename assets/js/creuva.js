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
 * Search dialog of the header module: opens with the magnifying glass, Ctrl+K
 * (⌘K) or "/" outside of form fields, closes with Escape, the × button or a
 * click on the backdrop.
 */
const initSearch = () => {
  document.querySelectorAll('[data-creuva-search-open]').forEach((button) => {
    const dialog = document.getElementById(button.getAttribute('aria-controls'));

    if (!dialog || typeof dialog.showModal !== 'function') {
      return;
    }

    const input = dialog.querySelector('input[type="search"]');
    const header = button.closest('.c-header');

    const open = () => {
      if (dialog.open) {
        return;
      }

      // Close the mobile navigation first
      if (header?.classList.contains('is-open')) {
        header.querySelector('[data-creuva-nav-toggle]')?.click();
      }

      dialog.showModal();
      document.documentElement.classList.add('has-open-search');
      input?.focus();
      input?.select();
    };

    button.hidden = false;
    button.addEventListener('click', open);

    dialog.querySelector('[data-creuva-search-close]')?.addEventListener('click', () => dialog.close());

    dialog.addEventListener('close', () => {
      document.documentElement.classList.remove('has-open-search');
      button.focus();
    });

    // Escape in a filled search field would only clear the field
    dialog.addEventListener('keydown', (event) => {
      if (event.key === 'Escape') {
        event.preventDefault();
        dialog.close();
      }
    });

    // Click on the backdrop (outside of the content)
    dialog.addEventListener('click', (event) => {
      if (event.target === dialog) {
        dialog.close();
      }
    });

    dialog.querySelector('form')?.addEventListener('submit', (event) => {
      if (input && '' === input.value.trim()) {
        event.preventDefault();
        input.focus();
      }
    });

    document.addEventListener('keydown', (event) => {
      const typing = event.target.closest?.('input, textarea, select, [contenteditable="true"]');

      if ((event.key === 'k' && (event.ctrlKey || event.metaKey)) || (event.key === '/' && !typing && !event.ctrlKey && !event.metaKey && !event.altKey)) {
        event.preventDefault();
        open();
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
    document.querySelectorAll('.l-main > .mod_article > :not(.c-hero, .mod_breadcrumb)').forEach((element) => {
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
 * Dates like "24.11.", "24.11" or "24.11.2026" are shown as they are (no unit,
 * no counting). Without the trailing dot, the month needs two digits, so
 * "1.500" or "4,9" stay numbers.
 */
const isDate = (text) => {
  const match = text.match(/^(\d{1,2})\.\s?(\d{1,2})\.(?:\s?\d{2,4})?$/) || text.match(/^(\d{1,2})\.(\d{2})$/);

  return !!match && +match[1] >= 1 && +match[1] <= 31 && +match[2] >= 1 && +match[2] <= 12;
};

/**
 * Ranges and other values with several numbers ("11–20 Uhr", "10–12 %"): the
 * text before the first and after the last digit is styled as unit, the value
 * does not count up.
 */
const splitRange = (text) => {
  const match = text.match(/^(\D*?)(\d.*\d)(\D*)$/);

  return match ? { prefix: match[1], raw: match[2], suffix: match[3] } : null;
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
    const text = term.textContent.trim();

    if (isDate(text)) {
      return;
    }

    const figure = parseFigure(text);

    if (!figure) {
      const range = splitRange(text);

      if (range) {
        term.innerHTML = `${unit(range.prefix)}<span class="c-stats__range">${escapeHtml(range.raw)}</span>${unit(range.suffix)}`;
      }

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

/**
 * Comparison table (CSS class u-table-compare): cells with "✓" or "–" (also
 * "x", "-") are shown as a check icon or a muted dash. The text stays for
 * screen readers.
 */
const initCompareTables = () => {
  document.querySelectorAll('.u-table-compare td').forEach((cell) => {
    const text = cell.textContent.trim();

    if (/^[✓✔xX]$/.test(text)) {
      cell.classList.add('is-yes');
    } else if (/^[–—-]$/.test(text)) {
      cell.classList.add('is-no');
    }
  });
};

/**
 * Lightbox for images with "full size view" (gallery and image element: links
 * with data-lightbox). Images with the same data-lightbox value form a group
 * with previous/next, keyboard (Esc, arrow keys) and swipe navigation. Contao's
 * own lightbox scripts (jQuery/MooTools) are not part of the Creuva layout.
 */
const initLightbox = () => {
  const isImage = (link) => /\.(jpe?g|png|gif|webp|avif|svg)(\?.*)?$/i.test(link.getAttribute('href') || '');
  const links = () => [...document.querySelectorAll('a[data-lightbox]')].filter(isImage);

  if (!links().length) {
    return;
  }

  const de = document.documentElement.lang.startsWith('de');
  const label = {
    dialog: de ? 'Bildansicht' : 'Image view',
    close: de ? 'Schließen' : 'Close',
    prev: de ? 'Vorheriges Bild' : 'Previous image',
    next: de ? 'Nächstes Bild' : 'Next image',
  };

  let dialog;
  let group = [];
  let index = 0;
  let opener = null;

  const build = () => {
    dialog = document.createElement('dialog');
    dialog.className = 'c-lightbox';
    dialog.setAttribute('aria-label', label.dialog);
    dialog.innerHTML = `
      <figure class="c-lightbox__figure">
        <img class="c-lightbox__image" alt="">
        <figcaption class="c-lightbox__caption"></figcaption>
      </figure>
      <p class="c-lightbox__counter" aria-live="polite"></p>
      <button type="button" class="c-lightbox__button c-lightbox__close" aria-label="${label.close}"></button>
      <button type="button" class="c-lightbox__button c-lightbox__prev" aria-label="${label.prev}"></button>
      <button type="button" class="c-lightbox__button c-lightbox__next" aria-label="${label.next}"></button>
    `;
    document.body.append(dialog);

    dialog.querySelector('.c-lightbox__close').addEventListener('click', () => dialog.close());
    dialog.querySelector('.c-lightbox__prev').addEventListener('click', () => show(index - 1));
    dialog.querySelector('.c-lightbox__next').addEventListener('click', () => show(index + 1));

    // Click on the backdrop (outside the image) closes the lightbox
    dialog.addEventListener('click', (event) => {
      if (event.target === dialog || event.target.classList.contains('c-lightbox__figure')) {
        dialog.close();
      }
    });

    dialog.addEventListener('keydown', (event) => {
      if (event.key === 'ArrowLeft') {
        show(index - 1);
      } else if (event.key === 'ArrowRight') {
        show(index + 1);
      }
    });

    // Swipe on touch devices
    let startX = null;
    dialog.addEventListener('touchstart', (event) => { startX = event.touches[0].clientX; }, { passive: true });
    dialog.addEventListener('touchend', (event) => {
      const distance = startX === null ? 0 : event.changedTouches[0].clientX - startX;
      startX = null;

      if (Math.abs(distance) > 50) {
        show(index + (distance < 0 ? 1 : -1));
      }
    });

    dialog.addEventListener('close', () => {
      document.documentElement.classList.remove('has-lightbox');
      opener?.focus();
    });
  };

  // Caption or title of the image (file metadata), not the alternative text:
  // that one describes the image for those who cannot see it
  const caption = (link) => {
    const figure = link.closest('figure');

    return (
      figure?.querySelector('figcaption')?.textContent.trim() ||
      link.getAttribute('title')?.trim() ||
      link.querySelector('img')?.getAttribute('title')?.trim() ||
      ''
    );
  };

  const preload = (i) => {
    const link = group[(i + group.length) % group.length];

    if (link) {
      new Image().src = link.href;
    }
  };

  const show = (i) => {
    index = (i + group.length) % group.length;

    const link = group[index];
    const image = dialog.querySelector('.c-lightbox__image');
    const text = caption(link);

    dialog.classList.add('is-loading');
    image.onload = () => dialog.classList.remove('is-loading');
    image.src = link.href;
    image.alt = link.querySelector('img')?.getAttribute('alt') || '';

    dialog.querySelector('.c-lightbox__caption').textContent = text;
    dialog.querySelector('.c-lightbox__caption').hidden = !text;
    dialog.querySelector('.c-lightbox__counter').textContent = group.length > 1 ? `${index + 1} / ${group.length}` : '';
    dialog.classList.toggle('is-single', group.length < 2);

    preload(index + 1);
    preload(index - 1);
  };

  document.addEventListener('click', (event) => {
    const link = event.target.closest('a[data-lightbox]');

    if (!link || !isImage(link) || event.defaultPrevented || event.button !== 0 || event.metaKey || event.ctrlKey || event.shiftKey) {
      return;
    }

    event.preventDefault();

    if (!dialog) {
      build();
    }

    opener = link;
    group = links().filter((item) => item.dataset.lightbox === link.dataset.lightbox);
    show(group.indexOf(link));
    document.documentElement.classList.add('has-lightbox');
    dialog.showModal();
  });
};

/**
 * Small copy of a slide image for its thumbnail: the same image variants
 * (srcset of the image and of the <source> elements, e.g. WebP) with the size
 * of the thumbnail, so the browser loads the smallest suitable file instead
 * of the large slide image.
 */
const createThumbImage = (image) => {
  // Thumbnails are at most 8rem wide (gallery slider, see _slider.scss)
  const sizes = '8rem';
  const thumb = document.createElement('img');

  thumb.src = image.getAttribute('src') || image.currentSrc;
  thumb.alt = '';
  thumb.loading = 'lazy';

  if (image.srcset) {
    thumb.srcset = image.srcset;
    thumb.sizes = sizes;
  }

  const picture = image.closest('picture');

  if (!picture) {
    return thumb;
  }

  const copy = document.createElement('picture');

  picture.querySelectorAll('source').forEach((source) => {
    const clone = document.createElement('source');

    ['type', 'media', 'srcset'].forEach((attribute) => source.hasAttribute(attribute) && clone.setAttribute(attribute, source.getAttribute(attribute)));
    clone.sizes = sizes;
    copy.append(clone);
  });

  copy.append(thumb);

  return copy;
};

/**
 * Thumbnails of the content slider (setting "Thumbnails"): a button per slide
 * with its first image (or its number), below the slider. Contao initializes
 * Swiper with an inline script at the end of the body, so the instance exists
 * when this runs (el.swiper).
 */
const initSliderThumbs = () => {
  document.querySelectorAll('.c-slider--thumbs').forEach((slider) => {
    const swiper = slider.querySelector('.swiper')?.swiper;

    if (!swiper || slider.querySelector('.c-slider__thumbs')) {
      return;
    }

    const label = slider.dataset.thumbLabel || '%s';
    const thumbs = document.createElement('div');
    thumbs.className = 'c-slider__thumbs';

    [...swiper.slides].forEach((slide) => {
      const index = Number(slide.dataset.swiperSlideIndex ?? swiper.slides.indexOf(slide));
      const image = slide.querySelector('img');
      const button = document.createElement('button');

      button.type = 'button';
      button.className = 'c-slider__thumb';
      button.dataset.index = String(index);
      button.setAttribute('aria-label', label.replace('%s', String(index + 1)));

      if (image) {
        button.append(createThumbImage(image));
      } else {
        button.textContent = String(index + 1);
      }

      button.addEventListener('click', () => (swiper.params.loop ? swiper.slideToLoop(index) : swiper.slideTo(index)));
      thumbs.append(button);
    });

    // Sorted by index (with "loop" Swiper may have reordered the slides)
    thumbs.replaceChildren(...[...thumbs.children].sort((a, b) => a.dataset.index - b.dataset.index));

    const update = () => {
      thumbs.querySelectorAll('.c-slider__thumb').forEach((button) => {
        const active = Number(button.dataset.index) === swiper.realIndex;
        button.classList.toggle('is-active', active);
        button.toggleAttribute('aria-current', active);
      });
    };

    swiper.on('slideChange', update);
    update();
    slider.querySelector('.swiper').after(thumbs);
  });
};

document.addEventListener('DOMContentLoaded', () => {
  initStickyHeader();
  initNavigation();
  initSearch();
  initStyleSwitcher();
  initReveal();
  initCountUp();
  initCompareTables();
  initLightbox();
  initSliderThumbs();
});
