<?php

declare(strict_types=1);

/*
 * Creuva theme bundle for Contao Open Source CMS
 *
 * Copyright (C) pdir GmbH <develop@pdir.de>
 */

namespace Pdir\CreuvaThemeBundle\EventListener;

use Contao\CoreBundle\DependencyInjection\Attribute\AsHook;

/**
 * Adds the identifier of the categories (<section>) and groups
 * (<section>.<group>) imported from config/css-styles.yaml to the tables of
 * the CSS style selector, see Pdir\CreuvaThemeBundle\Installer\CssStyleImporter.
 *
 * A hook instead of contao/dca/tl_css_style_group.php: the CSS style selector
 * defines its DCA as a whole array, so a DCA file of Creuva loaded before it
 * (e.g. in an application with config/bundles.php, which ignores the load
 * order of the Contao Manager plugin) would be overwritten.
 */
#[AsHook('loadDataContainer')]
class CssStyleGroupIdentifierListener
{
    public function __invoke(string $table): void
    {
        if (!\in_array($table, ['tl_css_style_category', 'tl_css_style_group'], true)) {
            return;
        }

        $GLOBALS['TL_DCA'][$table]['fields']['creuvaIdentifier'] = [
            'eval' => ['doNotCopy' => true],
            'sql' => ['type' => 'string', 'length' => 64, 'default' => ''],
        ];
    }
}
