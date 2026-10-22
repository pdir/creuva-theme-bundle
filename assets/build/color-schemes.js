/**
 * Color schemes of Creuva (config/color-schemes.json): SCSS generation and
 * WCAG contrast check. The derived colors mirror the color-mix() formulas in
 * assets/scss/base/_tokens.scss – keep both in sync.
 */
import { readFileSync } from 'node:fs';

const hex = (value) => {
  const m = /^#([0-9a-f]{6})$/i.exec(value);

  if (!m) {
    throw new Error(`Invalid color "${value}" (expected #rrggbb).`);
  }

  return [0, 2, 4].map((i) => parseInt(m[1].slice(i, i + 2), 16));
};

// color-mix(in srgb, a p, b)
const mix = (a, p, b) => a.map((c, i) => c * p + b[i] * (1 - p));

const luminance = (rgb) => {
  const [r, g, b] = rgb.map((c) => {
    const s = c / 255;

    return s <= 0.04045 ? s / 12.92 : ((s + 0.055) / 1.055) ** 2.4;
  });

  return 0.2126 * r + 0.7152 * g + 0.0722 * b;
};

export const contrast = (a, b) => {
  const [l1, l2] = [luminance(a), luminance(b)].sort((x, y) => y - x);

  return (l1 + 0.05) / (l2 + 0.05);
};

const WHITE = [255, 255, 255];

/**
 * Resolves the colors of a scheme like the browser does (light and dark variant).
 */
export const resolveScheme = (scheme) => {
  const primary = hex(scheme.primary);
  const accent = hex(scheme.accent);
  const accentText = scheme.accentText ? hex(scheme.accentText) : accent;
  const accentContrast = scheme.accentContrast ? hex(scheme.accentContrast) : WHITE;
  const darkAccent = scheme.darkAccent ? hex(scheme.darkAccent) : mix(accent, 0.6, WHITE);
  const darkBg = mix(primary, 0.3, hex('#080808'));

  return {
    light: {
      primary,
      bg: mix(primary, 0.03, WHITE),
      text: mix(primary, 0.65, [0, 0, 0]),
      textMuted: mix(primary, 0.45, hex('#808080')),
      accent,
      accentText,
      accentContrast,
    },
    dark: {
      bg: darkBg,
      surface: mix(primary, 0.42, hex('#0c0c0c')),
      textMuted: mix(primary, 0.2, hex('#d0d0d0')),
      accent: darkAccent,
      accentOnPrimary: mix(accent, 0.4, darkBg),
    },
  };
};

/**
 * @return list<string> failed checks
 */
export const checkScheme = (name, scheme) => {
  const { light, dark } = resolveScheme(scheme);
  const checks = [
    ['white text on primary (buttons)', WHITE, light.primary, 4.5],
    ['text on background', light.text, light.bg, 4.5],
    ['muted text on background', light.textMuted, light.bg, 4.5],
    ['accent text on background (links, kicker)', light.accentText, light.bg, 4.5],
    ['accent contrast on accent (badges)', light.accentContrast, light.accent, 4.5],
    ['accent on primary (headline accent in primary boxes)', mix(light.accent, 0.55, WHITE), light.primary, 3],
    ['dark: accent on background', dark.accent, dark.bg, 4.5],
    ['dark: accent on surface', dark.accent, dark.surface, 4.5],
    ['dark: background on accent (buttons)', dark.bg, dark.accent, 4.5],
    ['dark: muted text on surface', dark.textMuted, dark.surface, 4.5],
    ['dark: accent on primary (headline accent in primary boxes)', dark.accentOnPrimary, dark.accent, 3],
  ];

  return checks
    .map(([label, fg, bg, min]) => [label, contrast(fg, bg), min])
    .filter(([, ratio, min]) => ratio < min)
    .map(([label, ratio, min]) => `${name}: ${label} ${ratio.toFixed(2)}:1 < ${min}:1`);
};

export const loadSchemes = (file) => JSON.parse(readFileSync(file, 'utf8'));

/**
 * Returns the SCSS map consumed by assets/scss/base/_schemes.scss.
 */
export const toScss = ({ default: defaultScheme, schemes }) => {
  const entries = Object.entries(schemes).map(([name, scheme]) => {
    const props = { primary: scheme.primary, accent: scheme.accent };

    for (const [key, prop] of [['accentText', 'accent-text'], ['accentContrast', 'accent-contrast'], ['darkAccent', 'dark-accent']]) {
      if (scheme[key]) {
        props[prop] = scheme[key];
      }
    }

    const body = Object.entries(props).map(([k, v]) => `${k}: ${v}`).join(', ');

    return `  '${name}': (${body}),`;
  });

  return [
    '// Generated from config/color-schemes.json by vite.config.js – do not edit.',
    `$default: '${defaultScheme}';`,
    '$schemes: (',
    ...entries,
    ');',
    '',
  ].join('\n');
};
