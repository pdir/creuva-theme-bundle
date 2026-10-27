<?php

declare(strict_types=1);

/*
 * Creuva theme bundle for Contao Open Source CMS
 *
 * Copyright (C) pdir GmbH <develop@pdir.de>
 */

namespace Pdir\CreuvaThemeBundle\Design;

/**
 * Design options of the Creuva layout: variants, corner radii and the color
 * schemes defined in config/color-schemes.json (shared with the SCSS build).
 */
class DesignSettings
{
    /**
     * "auto" is light or dark depending on the system setting of the visitor
     * (resolved in the browser, see page/layout/creuva.html.twig).
     */
    public const VARIANTS = ['light', 'dark', 'auto', 'editorial'];

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
     * @return array{variant: string, scheme: string, radius: string, primary: string, accent: string, logoHeight: string, animations: string}
     */
    public function resolve(array|null $layout, array|null $rootPage = null): array
    {
        $variant = (string) ($layout['creuvaVariant'] ?? '');
        $scheme = (string) ($layout['creuvaScheme'] ?? '');

        // The former variant "corporate" is the color scheme "blue" now
        if ('corporate' === $variant) {
            [$variant, $scheme] = ['light', $scheme ?: 'blue'];
        }

        $radius = (string) ($layout['creuvaRadius'] ?? '');
        $animations = (string) ($layout['creuvaAnimations'] ?? '');

        $settings = [
            'variant' => \in_array($variant, self::VARIANTS, true) ? $variant : 'light',
            'scheme' => $this->hasScheme($scheme) ? $scheme : '',
            'radius' => \in_array($radius, self::RADII, true) ? $radius : 'large',
            'primary' => (string) ($layout['creuvaPrimaryColor'] ?? ''),
            'accent' => (string) ($layout['creuvaAccentColor'] ?? ''),
            'logoHeight' => preg_match(self::LOGO_HEIGHT_PATTERN, (string) ($layout['creuvaLogoHeight'] ?? '')) ? (string) $layout['creuvaLogoHeight'] : '',
            'animations' => \in_array($animations, self::ANIMATIONS, true) ? $animations : 'auto',
        ];

        if (null === $rootPage) {
            return $settings;
        }

        if (\in_array($rootPage['creuvaVariant'] ?? null, self::VARIANTS, true)) {
            $settings['variant'] = $rootPage['creuvaVariant'];
        }

        if (\in_array($rootPage['creuvaRadius'] ?? null, self::RADII, true)) {
            $settings['radius'] = $rootPage['creuvaRadius'];
        }

        if ($this->hasScheme((string) ($rootPage['creuvaScheme'] ?? ''))) {
            $settings['scheme'] = $rootPage['creuvaScheme'];
            $settings['primary'] = $settings['accent'] = '';
        }

        return $settings;
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
