import '../scss/backend.scss';

/**
 * Live preview of the color scheme in the page layout (tl_layout): reacts to
 * the scheme, the design variant, the corner radius and the custom colors.
 */
const LABELS = {
  de: {
    heading: 'Live-Vorschau',
    hint: 'Zeigt die Auswahl sofort an. Übernommen wird sie erst beim Speichern des Seitenlayouts.',
    kicker: 'Vorschau',
    title: ['Überschrift', 'mit Akzent'],
    text: ['Fließtext mit', 'einem Link', 'und', 'gedämpftem Text'],
    button: 'Button',
    secondary: 'Sekundär',
    badge: 'Beliebt',
    soft: ['Hinweisbox', 'Akzent-Hintergrund'],
    primary: ['Box', 'Primär', 'Text auf der Primärfarbe'],
    contrast: 'Zu wenig Kontrast für WCAG AA (mindestens 4,5:1):',
    primaryContrast: 'weiße Schrift auf der Primärfarbe',
    accentNotes: 'Damit alles WCAG-AA-konform lesbar bleibt, wird die Akzentfarbe angepasst:',
    accentText: (color) => `Links und kleine Texte im Light Mode in #${color}`,
    accentContrast: (color) => `Schrift auf Akzentflächen (Buttons, Badges) im Light Mode in #${color}`,
    darkAccent: (color) => `Akzentfarbe im Dark Mode #${color}`,
    darkAccentMuted: (color) => `nur auf grauen Flächen im Dark Mode (Kopf-/Fußleiste, graue Abschnitte und Karten) #${color}`,
  },
  en: {
    heading: 'Live preview',
    hint: 'Shows the selection right away. It is applied when the page layout is saved.',
    kicker: 'Preview',
    title: ['Headline', 'with accent'],
    text: ['Body text with', 'a link', 'and', 'muted text'],
    button: 'Button',
    secondary: 'Secondary',
    badge: 'Popular',
    soft: ['Info box', 'Accent background'],
    primary: ['Box', 'primary', 'Text on the primary color'],
    contrast: 'Not enough contrast for WCAG AA (at least 4.5:1):',
    primaryContrast: 'white text on the primary color',
    accentNotes: 'To keep everything readable according to WCAG AA, the accent color is adjusted:',
    accentText: (color) => `links and small text in light mode in #${color}`,
    accentContrast: (color) => `text on accent surfaces (buttons, badges) in light mode in #${color}`,
    darkAccent: (color) => `accent color in dark mode #${color}`,
    darkAccentMuted: (color) => `only on gray surfaces in dark mode (top/bottom bar, gray sections and cards) #${color}`,
  },
};

// WCAG contrast ratio of two hex colors (without #)
const luminance = (value) => {
  const [r, g, b] = [0, 2, 4].map((i) => {
    const c = parseInt(value.slice(i, i + 2), 16) / 255;

    return c <= 0.04045 ? c / 12.92 : ((c + 0.055) / 1.055) ** 2.4;
  });

  return 0.2126 * r + 0.7152 * g + 0.0722 * b;
};

const contrast = (a, b) => {
  const [l1, l2] = [luminance(a), luminance(b)].sort((x, y) => y - x);

  return (l1 + 0.05) / (l2 + 0.05);
};

const HEX = /^[0-9a-f]{6}$/i;

const toRgb = (value) => [0, 2, 4].map((i) => parseInt(value.slice(i, i + 2), 16));
const toHex = (rgb) => rgb.map((c) => Math.round(c).toString(16).padStart(2, '0')).join('');

// color-mix(in srgb, a share, b)
const mix = (a, share, b) => a.map((c, i) => c * share + b[i] * (1 - share));

const WHITE = [255, 255, 255];
const BLACK = [0, 0, 0];

// Mixes the color with the target in steps of 5 % until it is readable on all surfaces
const adjust = (color, target, surfaces) => {
  for (let step = 20; step >= 0; step -= 1) {
    const candidate = toHex(mix(toRgb(color), step / 20, target));

    if (surfaces(toRgb(candidate)).every((surface) => contrast(candidate, toHex(surface)) >= 4.5)) {
      return candidate;
    }
  }

  return toHex(target);
};

/**
 * Derived colors of a custom accent color, so that the accent itself is used
 * wherever it is readable (text color in the light mode, text on the accent,
 * accent of the dark mode). Same calculation as
 * DesignSettings::getAccentColors() – keep both in sync.
 */
const accentColors = (accent, primary) => {
  const a = toRgb(accent);
  const p = toRgb(primary);
  const light = [mix(p, 0.03, WHITE), WHITE, mix(p, 0.07, WHITE), mix(a, 0.1, WHITE)];
  const textColor = toHex(mix(p, 0.65, BLACK));
  const darkBg = mix(p, 0.3, [8, 8, 8]);
  const dark = [darkBg, mix(p, 0.42, [12, 12, 12])];
  const darkMuted = mix(p, 0.62, [14, 14, 14]);
  const darkAccent = adjust(accent, WHITE, (rgb) => [...dark, mix(rgb, 0.14, darkBg), mix(darkBg, 0.85, rgb)]);

  return {
    accentText: adjust(accent, BLACK, () => light),
    accentContrast: ['ffffff', textColor].find((color) => contrast(color, accent) >= 4.5) ?? '000000',
    darkAccent,
    darkAccentMuted: adjust(darkAccent, WHITE, () => [darkMuted]),
  };
};

/**
 * Surface in the primary color in the dark mode and the highlighted text on it.
 * Same calculation as DesignSettings::getDarkPrimaryColors() – keep both in sync.
 */
const darkPrimaryColors = (primary, darkAccent) => {
  const p = toRgb(primary);
  const darkBg = mix(p, 0.3, [8, 8, 8]);
  let darkPrimary = primary;

  for (let step = 20; step >= 0; step -= 1) {
    const candidate = toHex(mix(p, step / 20, WHITE));

    if (contrast('ffffff', candidate) < 4.5) {
      break;
    }

    darkPrimary = candidate;

    if (contrast(candidate, toHex(darkBg)) >= 2) {
      break;
    }
  }

  return { darkPrimary, darkAccentOnPrimary: adjust(darkAccent, WHITE, () => [toRgb(darkPrimary)]) };
};

const SCHEME_PROPERTIES = ['primary', 'accent', 'accent-text', 'accent-contrast', 'dark-accent', 'dark-accent-muted', 'dark-primary', 'dark-primary-contrast', 'dark-accent-on-primary'];

const render = (t) => `
  <h3>${t.heading}</h3>
  <div class="creuva-preview" aria-hidden="true">
    <p class="creuva-preview__kicker">${t.kicker}</p>
    <p class="creuva-preview__title">${t.title[0]} <span>${t.title[1]}</span></p>
    <p class="creuva-preview__text">${t.text[0]} <u>${t.text[1]}</u> ${t.text[2]} <span>${t.text[3]}</span>.</p>
    <p class="creuva-preview__actions">
      <span class="creuva-preview__button">${t.button}</span>
      <span class="creuva-preview__button creuva-preview__button--secondary">${t.secondary}</span>
      <span class="creuva-preview__badge">${t.badge}</span>
    </p>
    <div class="creuva-preview__cards">
      <div class="creuva-preview__card creuva-preview__card--soft"><strong>${t.soft[0]}</strong>${t.soft[1]}</div>
      <div class="creuva-preview__card creuva-preview__card--primary"><strong>${t.primary[0]} <span>${t.primary[1]}</span></strong>${t.primary[2]}</div>
    </div>
  </div>
  <p class="tl_help tl_tip">${t.hint}</p>`;

const initPreview = () => {
  const picker = document.querySelector('.creuva-scheme-picker');
  const form = picker?.closest('form');

  if (!form || picker.dataset.creuvaPreview) {
    return;
  }

  picker.dataset.creuvaPreview = 'true';

  const container = document.createElement('div');
  container.className = 'widget clr';
  container.innerHTML = render(LABELS[document.documentElement.lang?.startsWith('de') ? 'de' : 'en']);
  picker.after(container);

  const preview = container.querySelector('.creuva-preview');
  const labels = LABELS[document.documentElement.lang?.startsWith('de') ? 'de' : 'en'];

  // Contrast warning of the custom colors (they are used like in page/layout/creuva.html.twig)
  const warning = document.createElement('p');
  warning.className = 'tl_error creuva-contrast-warning';
  warning.hidden = true;
  preview.after(warning);

  // Note on the colors derived from the custom accent color
  const accentNote = document.createElement('p');
  accentNote.className = 'tl_help tl_tip creuva-accent-note';
  accentNote.hidden = true;
  warning.after(accentNote);
  const value = (name) => form.querySelector(`[name="${name}"]`)?.value.trim().replace(/^#/, '') ?? '';

  const update = () => {
    const scheme = form.querySelector('input[name="creuvaScheme"]:checked')?.value;

    if (scheme) {
      preview.dataset.scheme = scheme;
    } else {
      delete preview.dataset.scheme;
    }

    const variant = value('creuvaVariant') || 'light';

    // "auto": preview like the system setting of the editor
    preview.dataset.theme = 'auto' === variant ? (matchMedia('(prefers-color-scheme: dark)').matches ? 'dark' : 'light') : variant;
    preview.dataset.typography = value('creuvaTypography') || 'modern';
    preview.dataset.radius = value('creuvaRadius') || 'large';

    // Custom colors override the scheme like in page/layout/creuva.html.twig
    SCHEME_PROPERTIES.forEach((property) => preview.style.removeProperty(`--scheme-${property}`));

    const primary = value('creuvaPrimaryColor');
    const accent = value('creuvaAccentColor');

    if (HEX.test(primary)) {
      preview.style.setProperty('--scheme-primary', `#${primary}`);
    }

    accentNote.hidden = true;

    if (HEX.test(accent)) {
      // The surfaces use the primary color of the scheme without a custom one
      const surfacePrimary = getComputedStyle(preview).getPropertyValue('--scheme-primary').trim().replace(/^#/, '');
      const color = accent.toLowerCase();
      const derived = HEX.test(surfacePrimary) ? accentColors(color, surfacePrimary) : { accentText: color, accentContrast: 'ffffff', darkAccent: color, darkAccentMuted: color };

      preview.style.setProperty('--scheme-accent', `#${color}`);
      preview.style.setProperty('--scheme-accent-text', `#${derived.accentText}`);
      preview.style.setProperty('--scheme-accent-contrast', `#${derived.accentContrast}`);
      preview.style.setProperty('--scheme-dark-accent', `#${derived.darkAccent}`);
      preview.style.setProperty('--scheme-dark-accent-muted', `#${derived.darkAccentMuted}`);

      const notes = [
        derived.accentText !== color && labels.accentText(derived.accentText),
        'ffffff' !== derived.accentContrast && labels.accentContrast(derived.accentContrast),
        derived.darkAccent !== color && labels.darkAccent(derived.darkAccent),
        derived.darkAccentMuted !== derived.darkAccent && labels.darkAccentMuted(derived.darkAccentMuted),
      ].filter(Boolean);

      accentNote.textContent = `${labels.accentNotes} ${notes.join(', ')}.`;
      accentNote.hidden = !notes.length;
    }

    // Surfaces in the primary color in the dark mode, for custom colors (the
    // schemes get them from the build)
    if (HEX.test(primary) || HEX.test(accent)) {
      const styles = getComputedStyle(preview);
      const hex = (property) => styles.getPropertyValue(property).trim().replace(/^#/, '');
      const surfacePrimary = hex('--scheme-primary');
      let darkAccent = hex('--scheme-dark-accent');

      // Schemes without a dark accent of their own: 60 % accent, 40 % white
      if (!HEX.test(darkAccent) && HEX.test(hex('--scheme-accent'))) {
        darkAccent = toHex(mix(toRgb(hex('--scheme-accent')), 0.6, WHITE));
      }

      if (HEX.test(surfacePrimary) && HEX.test(darkAccent)) {
        const surface = darkPrimaryColors(surfacePrimary.toLowerCase(), darkAccent.toLowerCase());

        preview.style.setProperty('--scheme-dark-primary', `#${surface.darkPrimary}`);
        preview.style.setProperty('--scheme-dark-primary-contrast', '#fff');
        preview.style.setProperty('--scheme-dark-accent-on-primary', `#${surface.darkAccentOnPrimary}`);
      }
    }

    // Text colors derived from the accent are always readable, only white text
    // on a custom primary color can fail
    const ratio = (a, b) => `${contrast(a, b).toLocaleString(document.documentElement.lang || 'en', { maximumFractionDigits: 2 })}:1`;
    const problems = [
      HEX.test(primary) && contrast('ffffff', primary) < 4.5 && `${labels.primaryContrast} (${ratio('ffffff', primary)})`,
    ].filter(Boolean);

    warning.hidden = !problems.length;
    warning.textContent = problems.length ? `${labels.contrast} ${problems.join(', ')}` : '';
  };

  form.addEventListener('change', update);
  form.addEventListener('input', update);
  update();

  // The color picker of Contao (Pickr) starts transparent if the field is empty:
  // start with the color of the selected scheme instead. Typing into the result
  // field of Pickr only changes the picker, the field is set on "Save".
  Object.entries({ creuvaPrimaryColor: '--scheme-primary', creuvaAccentColor: '--scheme-accent' }).forEach(([name, property]) => {
    const input = form.querySelector(`[name="${name}"]`);
    const widget = input?.closest('[data-controller~="contao--color-picker"]');

    widget?.addEventListener('click', (event) => {
      if (!event.target.closest('[data-contao--color-picker-target="button"]') || input.value.trim()) {
        return;
      }

      const color = getComputedStyle(preview).getPropertyValue(property).trim();

      // Pickr shows its panel after the click
      setTimeout(() => {
        const result = document.querySelector('.pcr-app.visible .pcr-result');

        if (color && result) {
          result.value = color;
          result.dispatchEvent(new Event('input', { bubbles: true }));
        }
      });
    });
  });
};

// The back end uses Turbo, so the page is not always loaded from scratch
document.addEventListener('turbo:load', initPreview);
document.addEventListener('DOMContentLoaded', initPreview);

if (document.readyState !== 'loading') {
  initPreview();
}
