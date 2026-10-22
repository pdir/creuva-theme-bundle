<?php

declare(strict_types=1);

/*
 * Creuva theme bundle for Contao Open Source CMS
 *
 * Copyright (C) pdir GmbH <develop@pdir.de>
 */

namespace Pdir\CreuvaThemeBundle\EventListener\DataContainer;

use Contao\CoreBundle\DependencyInjection\Attribute\AsCallback;
use Pdir\CreuvaThemeBundle\Design\DesignSettings;
use Symfony\Component\Asset\Packages;

/**
 * Color scheme picker in the page layout: adds the swatches and the live
 * preview (public/backend.css and backend.js) and preselects the default
 * scheme for layouts without a scheme.
 */
class ColorSchemePickerListener
{
    public function __construct(
        private readonly DesignSettings $designSettings,
        private readonly Packages $packages,
    ) {
    }

    #[AsCallback(table: 'tl_layout', target: 'config.onload')]
    public function addAssets(): void
    {
        $GLOBALS['TL_CSS'][] = $this->packages->getUrl('backend.css', 'creuva_theme');
        $GLOBALS['TL_JAVASCRIPT'][] = $this->packages->getUrl('backend.js', 'creuva_theme');
    }

    #[AsCallback(table: 'tl_layout', target: 'fields.creuvaScheme.load')]
    public function loadScheme(mixed $value): string
    {
        $value = (string) $value;

        return $this->designSettings->hasScheme($value) ? $value : $this->designSettings->getDefaultScheme();
    }
}
