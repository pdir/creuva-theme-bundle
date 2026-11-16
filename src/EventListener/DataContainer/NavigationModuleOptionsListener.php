<?php

declare(strict_types=1);

/*
 * Creuva theme bundle for Contao Open Source CMS
 *
 * Copyright (C) pdir GmbH <develop@pdir.dev>
 */

namespace Pdir\CreuvaThemeBundle\EventListener\DataContainer;

use Contao\CoreBundle\DependencyInjection\Attribute\AsCallback;
use Contao\DataContainer;
use Doctrine\DBAL\Connection;

/**
 * Offers the navigation modules of the same theme for the header module.
 */
#[AsCallback(table: 'tl_module', target: 'fields.creuvaNavigation.options')]
class NavigationModuleOptionsListener
{
    public function __construct(private readonly Connection $connection)
    {
    }

    public function __invoke(DataContainer|null $dc = null): array
    {
        $pid = (int) ($dc?->getCurrentRecord()['pid'] ?? 0);

        $modules = $this->connection->fetchAllKeyValue(
            "SELECT id, name FROM tl_module WHERE type IN ('navigation', 'customnav') AND pid = ? ORDER BY name",
            [$pid],
        );

        return array_map('strval', $modules);
    }
}
