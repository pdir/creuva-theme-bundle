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
    accentOnWhite: 'Akzentfarbe als Schrift auf Weiß',
    whiteOnAccent: 'weiße Schrift auf der Akzentfarbe',
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
    accentOnWhite: 'accent color as text on white',
    whiteOnAccent: 'white text on the accent color',
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
const SCHEME_PROPERTIES = ['primary', 'accent', 'accent-text', 'accent-contrast', 'dark-accent'];

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

    if (HEX.test(accent)) {
      preview.style.setProperty('--scheme-accent', `#${accent}`);
      preview.style.setProperty('--scheme-accent-text', `#${accent}`);
      preview.style.setProperty('--scheme-accent-contrast', '#fff');
      preview.style.setProperty('--scheme-dark-accent', `color-mix(in srgb, #${accent} 60%, #fff)`);
    }

    const ratio = (a, b) => `${contrast(a, b).toLocaleString(document.documentElement.lang || 'en', { maximumFractionDigits: 2 })}:1`;
    const problems = [
      HEX.test(primary) && contrast('ffffff', primary) < 4.5 && `${labels.primaryContrast} (${ratio('ffffff', primary)})`,
      HEX.test(accent) && contrast(accent, 'ffffff') < 4.5 && `${labels.accentOnWhite} (${ratio(accent, 'ffffff')})`,
    ].filter(Boolean);

    // White text on the accent has the same ratio as the accent on white
    if (HEX.test(accent) && contrast(accent, 'ffffff') < 4.5) {
      problems.push(labels.whiteOnAccent);
    }

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
