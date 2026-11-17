<?php

declare(strict_types=1);

/*
 * Creuva theme bundle for Contao Open Source CMS
 *
 * Copyright (C) pdir GmbH <develop@pdir.dev>
 */

namespace Pdir\CreuvaThemeBundle\Design;

/**
 * Design options of the Creuva layout: color modes (variants), typography,
 * corner radii and the color schemes defined in config/color-schemes.json
 * (shared with the SCSS build).
 */
class DesignSettings
{
    /**
     * Color mode. "auto" is light or dark depending on the system setting of
     * the visitor (resolved in the browser, see page/layout/creuva.html.twig).
     */
    public const VARIANTS = ['light', 'dark', 'auto'];

    /**
     * Typography, independent of the color mode: "editorial" uses a serif font
     * for the headings.
     */
    public const TYPOGRAPHIES = ['modern', 'editorial'];

    public const RADII = ['none', 'small', 'medium', 'large'];

    /**
     * Animations: all sections (auto), only elements with a u-reveal-* class
     * (classes) or none at all.
     */
    public const ANIMATIONS = ['auto', 'classes', 'none'];

    /**
     * CSS length for the logo height, e.g. "4rem", "3.5em" or "64px".
     */
    public const LOGO_HEIGHT_PATTERN = '/^\d{1,3}(\.\d{1,3})?(rem|em|px)$/';

    /**
     * Minimum contrast of the colors derived from a custom accent color (WCAG
     * AA for text), see getAccentColors().
     */
    public const ACCENT_CONTRAST = 4.5;

    /**
     * Highlighted headline of the full screen hero (large text on the image):
     * minimum contrast (WCAG AA for large text) and the darkest gradient behind
     * the text that may be used to reach it, see getAccentColors().
     */
    public const HERO_CONTRAST = 3.0;

    public const HERO_SHADE_MAX = 0.75;

    /**
     * @var array{default: string, schemes: array<string, array{label: array<string, string>, primary: string, accent: string}>}|null
     */
    private array|null $data = null;

    public function __construct(private readonly string $schemesFile = __DIR__.'/../../config/color-schemes.json')
    {
    }

    public function getDefaultScheme(): string
    {
        return $this->load()['default'];
    }

    /**
     * @return list<string> scheme names, the default scheme first
     */
    public function getSchemeNames(): array
    {
        $names = array_keys($this->load()['schemes']);

        return array_values(array_unique([$this->getDefaultScheme(), ...$names]));
    }

    public function hasScheme(string $name): bool
    {
        return isset($this->load()['schemes'][$name]);
    }

    /**
     * @return array<string, array{label: string, primary: string, accent: string}>
     */
    public function getSchemes(string $locale): array
    {
        $schemes = [];

        foreach ($this->getSchemeNames() as $name) {
            $scheme = $this->load()['schemes'][$name];

            $schemes[$name] = [
                'label' => $this->getLabel($scheme['label'] ?? [], $locale) ?? $name,
                'primary' => $scheme['primary'],
                'accent' => $scheme['accent'],
            ];
        }

        return $schemes;
    }

    /**
     * Combines the settings of the page layout and the website root (the root
     * page overrides the layout). A scheme of the root page also replaces the
     * custom colors of the layout, just like the style switcher does.
     *
     * @param array<string, mixed>|null $layout
     * @param array<string, mixed>|null $rootPage
     *
     * @return array{variant: string, typography: string, scheme: string, radius: string, primary: string, accent: string, accentText: string, accentContrast: string, darkAccent: string, darkAccentMuted: string, darkAccentOnPrimary: string, heroAccent: string, heroShade: string, logoHeight: string, animations: string}
     */
    public function resolve(array|null $layout, array|null $rootPage = null): array
    {
        $variant = (string) ($layout['creuvaVariant'] ?? '');
        $typography = (string) ($layout['creuvaTypography'] ?? '');
        $scheme = (string) ($layout['creuvaScheme'] ?? '');

        // The former variant "corporate" is the color scheme "blue" now
        if ('corporate' === $variant) {
            [$variant, $scheme] = ['light', $scheme ?: 'blue'];
        }

        // The former variant "editorial" is the light mode with the editorial typography now
        if ('editorial' === $variant) {
            [$variant, $typography] = ['light', 'editorial'];
        }

        $radius = (string) ($layout['creuvaRadius'] ?? '');
        $animations = (string) ($layout['creuvaAnimations'] ?? '');

        $settings = [
            'variant' => \in_array($variant, self::VARIANTS, true) ? $variant : 'light',
            'typography' => \in_array($typography, self::TYPOGRAPHIES, true) ? $typography : 'modern',
            'scheme' => $this->hasScheme($scheme) ? $scheme : '',
            'radius' => \in_array($radius, self::RADII, true) ? $radius : 'large',
            'primary' => (string) ($layout['creuvaPrimaryColor'] ?? ''),
            'accent' => (string) ($layout['creuvaAccentColor'] ?? ''),
            'logoHeight' => preg_match(self::LOGO_HEIGHT_PATTERN, (string) ($layout['creuvaLogoHeight'] ?? '')) ? (string) $layout['creuvaLogoHeight'] : '',
            'animations' => \in_array($animations, self::ANIMATIONS, true) ? $animations : 'auto',
        ];

        if (null === $rootPage) {
            return $this->withAccentColors($settings);
        }

        if (\in_array($rootPage['creuvaVariant'] ?? null, self::VARIANTS, true)) {
            $settings['variant'] = $rootPage['creuvaVariant'];
        } elseif ('editorial' === ($rootPage['creuvaVariant'] ?? null)) {
            [$settings['variant'], $settings['typography']] = ['light', 'editorial'];
        }

        if (\in_array($rootPage['creuvaTypography'] ?? null, self::TYPOGRAPHIES, true)) {
            $settings['typography'] = $rootPage['creuvaTypography'];
        }

        if (\in_array($rootPage['creuvaRadius'] ?? null, self::RADII, true)) {
            $settings['radius'] = $rootPage['creuvaRadius'];
        }

        if ($this->hasScheme((string) ($rootPage['creuvaScheme'] ?? ''))) {
            $settings['scheme'] = $rootPage['creuvaScheme'];
            $settings['primary'] = $settings['accent'] = '';
        }

        return $this->withAccentColors($settings);
    }

    /**
     * Derived colors of a custom accent color (hex values without #), so that
     * the accent itself is used wherever it is readable (WCAG AA, 4.5:1):
     *
     * - accentText: links and small text in the light mode – the accent itself
     *   or darkened in steps of 5 % until it is readable on all light surfaces
     * - accentContrast: text on accent surfaces (buttons, badges) – white, the
     *   text color or black, whichever is readable first
     * - darkAccent: accent of the dark mode – the accent itself or lightened in
     *   steps of 5 % until it is readable on the background and the cards
     * - darkAccentMuted: the same for the gray surfaces of the dark mode (top
     *   and bottom bar, u-bg-muted, u-card-muted), only used there
     * - darkAccentOnPrimary: highlighted text on the accent surfaces of the dark
     *   mode (u-bg-primary, CTA box) – 40 % accent and 60 % background, mixed
     *   further with the background until it is readable
     * - heroAccent, heroShade: highlighted headline of the full screen hero –
     *   the accent itself on a gradient that is just dark enough (heroShade,
     *   black share) for 3:1 even on a white image; if that would need more
     *   than HERO_SHADE_MAX, the accent is lightened in steps of 5 % instead
     *
     * The surfaces follow the formulas in assets/scss/base/_color-tokens.scss,
     * the same calculation is done in assets/js/backend.js for the preview –
     * keep all three in sync.
     *
     * @return array{accentText: string, accentContrast: string, darkAccent: string, darkAccentMuted: string, darkAccentOnPrimary: string, heroAccent: string, heroShade: string}
     */
    public function getAccentColors(string $accent, string $primary): array
    {
        $accent = self::hexToRgb($accent);
        $primary = self::hexToRgb($primary);
        $white = [255.0, 255.0, 255.0];
        $black = [0.0, 0.0, 0.0];

        // Light mode: --c-bg, --c-surface, --c-surface-muted and --c-accent-soft
        $light = [self::mix($primary, 0.03, $white), $white, self::mix($primary, 0.07, $white), self::mix($accent, 0.1, $white)];
        $accentText = self::adjust($accent, $black, static fn (array $color): array => $light);

        $textColor = array_map(round(...), self::mix($primary, 0.65, $black));
        $accentContrast = $black;

        foreach ([$white, $textColor] as $candidate) {
            if (self::contrast($candidate, $accent) >= self::ACCENT_CONTRAST) {
                $accentContrast = $candidate;
                break;
            }
        }

        // Dark mode: --c-bg, --c-surface, --c-accent-soft and muted text on accent
        // surfaces; --c-surface-muted separately
        $darkBg = self::mix($primary, 0.3, [8.0, 8.0, 8.0]);
        $dark = [$darkBg, self::mix($primary, 0.42, [12.0, 12.0, 12.0])];
        $darkAccent = self::adjust($accent, $white, static fn (array $color): array => [...$dark, self::mix($color, 0.14, $darkBg), self::mix($darkBg, 0.85, $color)]);
        $darkMuted = self::mix($primary, 0.62, [14.0, 14.0, 14.0]);
        $darkAccentMuted = self::adjust($darkAccent, $white, static fn (array $color): array => [$darkMuted]);
        $darkAccentOnPrimary = self::adjust(self::mix($accent, 0.4, $darkBg), $darkBg, static fn (array $color): array => [$darkAccent]);

        // Full screen hero: worst case is a white image under the gradient
        $heroAccent = $white;
        $heroShade = self::heroShade($white);

        for ($step = 20; $step >= 0; --$step) {
            $candidate = array_map(round(...), self::mix($accent, $step / 20, $white));
            $shade = self::heroShade($candidate);

            if (null !== $shade && $shade <= self::HERO_SHADE_MAX) {
                [$heroAccent, $heroShade] = [$candidate, $shade];
                break;
            }
        }

        return [
            'accentText' => self::rgbToHex($accentText),
            'accentContrast' => self::rgbToHex($accentContrast),
            'darkAccent' => self::rgbToHex($darkAccent),
            'darkAccentMuted' => self::rgbToHex($darkAccentMuted),
            'darkAccentOnPrimary' => self::rgbToHex($darkAccentOnPrimary),
            'heroAccent' => self::rgbToHex($heroAccent),
            'heroShade' => \sprintf('%.2F', $heroShade),
        ];
    }

    /**
     * @param array<string, string> $settings
     *
     * @return array<string, string>
     */
    private function withAccentColors(array $settings): array
    {
        $settings += ['accentText' => '', 'accentContrast' => '', 'darkAccent' => '', 'darkAccentMuted' => '', 'darkAccentOnPrimary' => '', 'heroAccent' => '', 'heroShade' => ''];

        if (!preg_match('/^[0-9a-f]{6}$/i', $settings['accent'])) {
            return $settings;
        }

        // Without a custom primary color, the surfaces use the one of the scheme
        $primary = $settings['primary'];

        if (!preg_match('/^[0-9a-f]{6}$/i', $primary)) {
            $schemes = $this->load()['schemes'];
            $primary = ($schemes[$settings['scheme']] ?? $schemes[$this->getDefaultScheme()])['primary'];
        }

        return [...$settings, ...$this->getAccentColors($settings['accent'], $primary)];
    }

    /**
     * Mixes the color with the target (black or white) in steps of 5 % until
     * it is readable on all surfaces returned by the callback.
     *
     * @param array<float>                            $color
     * @param array<float>                            $target
     * @param callable(array<float>): list<array<float>> $surfaces
     *
     * @return array<float>
     */
    private static function adjust(array $color, array $target, callable $surfaces): array
    {
        for ($step = 20; $step >= 0; --$step) {
            $candidate = array_map(round(...), self::mix($color, $step / 20, $target));

            foreach ($surfaces($candidate) as $surface) {
                if (self::contrast($candidate, $surface) < self::ACCENT_CONTRAST) {
                    continue 2;
                }
            }

            return $candidate;
        }

        return $target;
    }

    /**
     * Smallest share of black over a white image (in steps of 1 %) on which the
     * color has a contrast of HERO_CONTRAST as lighter text, null if none.
     *
     * @param array<float> $color
     */
    private static function heroShade(array $color): float|null
    {
        for ($percent = 0; $percent <= 100; ++$percent) {
            $gray = 255 * (1 - $percent / 100);
            $background = [$gray, $gray, $gray];

            if (self::contrast($color, $background) >= self::HERO_CONTRAST && self::contrast($color, [0.0, 0.0, 0.0]) > self::contrast($background, [0.0, 0.0, 0.0])) {
                return $percent / 100;
            }
        }

        return null;
    }

    /**
     * @param array<float> $rgb
     */
    private static function rgbToHex(array $rgb): string
    {
        return vsprintf('%02x%02x%02x', array_map(static fn (float $c): int => (int) round($c), $rgb));
    }

    /**
     * @return array{float, float, float}
     */
    private static function hexToRgb(string $hex): array
    {
        $hex = ltrim($hex, '#');

        return [(float) hexdec(substr($hex, 0, 2)), (float) hexdec(substr($hex, 2, 2)), (float) hexdec(substr($hex, 4, 2))];
    }

    /**
     * color-mix(in srgb, $a $share, $b).
     *
     * @param array<float> $a
     * @param array<float> $b
     *
     * @return array<float>
     */
    private static function mix(array $a, float $share, array $b): array
    {
        return array_map(static fn (float $x, float $y): float => $x * $share + $y * (1 - $share), $a, $b);
    }

    /**
     * WCAG contrast ratio of two colors.
     *
     * @param array<float> $a
     * @param array<float> $b
     */
    private static function contrast(array $a, array $b): float
    {
        $luminance = static function (array $rgb): float {
            [$r, $g, $b] = array_map(static function (float $c): float {
                $c /= 255;

                return $c <= 0.04045 ? $c / 12.92 : (($c + 0.055) / 1.055) ** 2.4;
            }, $rgb);

            return 0.2126 * $r + 0.7152 * $g + 0.0722 * $b;
        };

        $l1 = $luminance($a);
        $l2 = $luminance($b);

        return (max($l1, $l2) + 0.05) / (min($l1, $l2) + 0.05);
    }

    /**
     * @param array<string, string> $labels
     */
    private function getLabel(array $labels, string $locale): string|null
    {
        $language = strtolower(substr(str_replace('-', '_', $locale), 0, 2));

        return $labels[$language] ?? $labels['en'] ?? null;
    }

    private function load(): array
    {
        return $this->data ??= json_decode((string) file_get_contents($this->schemesFile), true, 512, JSON_THROW_ON_ERROR);
    }
}
