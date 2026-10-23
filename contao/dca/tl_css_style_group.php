<?php

declare(strict_types=1);

/*
 * Creuva theme bundle for Contao Open Source CMS
 *
 * Copyright (C) pdir GmbH <develop@pdir.de>
 */

// Identifier of the groups imported from config/css-styles.yaml (<section>.<group>),
// see Pdir\CreuvaThemeBundle\Installer\CssStyleImporter
$GLOBALS['TL_DCA']['tl_css_style_group']['fields']['creuvaIdentifier'] = [
    'eval' => ['doNotCopy' => true],
    'sql' => ['type' => 'string', 'length' => 64, 'default' => ''],
];
