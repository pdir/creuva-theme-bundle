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
use Symfony\Component\HttpFoundation\RequestStack;

/**
 * Offers the color schemes of config/color-schemes.json in the page layout.
 */
#[AsCallback(table: 'tl_layout', target: 'fields.creuvaScheme.options')]
class ColorSchemeOptionsListener
{
    public function __construct(
        private readonly DesignSettings $designSettings,
        private readonly RequestStack $requestStack,
    ) {
    }

    public function __invoke(): array
    {
        $locale = $this->requestStack->getCurrentRequest()?->getLocale() ?? 'en';

        return array_map(static fn (array $scheme): string => $scheme['label'], $this->designSettings->getSchemes($locale));
    }
}
