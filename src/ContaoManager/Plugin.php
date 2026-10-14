<?php

declare(strict_types=1);

/*
 * Creuva theme bundle for Contao Open Source CMS
 *
 * Copyright (C) pdir GmbH <develop@pdir.de>
 */

namespace Pdir\CreuvaThemeBundle\ContaoManager;

use Contao\CoreBundle\ContaoCoreBundle;
use Contao\ManagerPlugin\Bundle\BundlePluginInterface;
use Contao\ManagerPlugin\Bundle\Config\BundleConfig;
use Contao\ManagerPlugin\Bundle\Parser\ParserInterface;
use ContaoThemesNet\ThemeComponentsBundle\ThemeComponentsBundle;
use Pdir\CreuvaThemeBundle\CreuvaThemeBundle;

class Plugin implements BundlePluginInterface
{
    public function getBundles(ParserInterface $parser): array
    {
        return [
            BundleConfig::create(CreuvaThemeBundle::class)
                ->setLoadAfter([
                    ContaoCoreBundle::class,
                    ThemeComponentsBundle::class,
                ]),
        ];
    }
}
