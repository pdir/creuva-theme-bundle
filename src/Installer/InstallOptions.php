<?php

declare(strict_types=1);

/*
 * Creuva theme bundle for Contao Open Source CMS
 *
 * Copyright (C) pdir GmbH <develop@pdir.de>
 */

namespace Pdir\CreuvaThemeBundle\Installer;

final class InstallOptions
{
    public function __construct(
        public readonly string $themeName = 'Creuva',
        public readonly string $variant = 'light',
        public readonly bool $createPages = false,
        public readonly string $siteTitle = 'Creuva',
        public readonly string $language = 'de',
        public readonly string $dns = '',
        public readonly string|null $demo = null,
    ) {
    }
}
