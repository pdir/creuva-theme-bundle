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
    public const VARIANTS = ['light', 'dark', 'editorial'];

    public const RADII = ['none', 'small', 'medium', 'large'];

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
