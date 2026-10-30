<?php

declare(strict_types=1);

/*
 * Creuva theme bundle for Contao Open Source CMS
 *
 * Copyright (C) pdir GmbH <develop@pdir.de>
 */

use Contao\CoreBundle\DataContainer\PaletteManipulator;
use Pdir\CreuvaThemeBundle\Design\DesignSettings;

/*
 * Design settings of the website root: they override the page layout and can
 * also be saved from the style switcher in the front end preview.
 */
PaletteManipulator::create()
    ->addLegend('creuva_legend', 'layout_legend', PaletteManipulator::POSITION_AFTER, true)
    ->addField(['creuvaScheme', 'creuvaVariant', 'creuvaTypography', 'creuvaRadius'], 'creuva_legend', PaletteManipulator::POSITION_APPEND)
    ->applyToPalette('root', 'tl_page')
    ->applyToPalette('rootfallback', 'tl_page')
;

// Options from config/color-schemes.json, see ColorSchemeOptionsListener
$GLOBALS['TL_DCA']['tl_page']['fields']['creuvaScheme'] = [
    'inputType' => 'select',
    'eval' => ['includeBlankOption' => true, 'blankOptionLabel' => &$GLOBALS['TL_LANG']['tl_page']['creuvaFromLayout'], 'tl_class' => 'w50'],
    'sql' => ['type' => 'string', 'length' => 32, 'default' => ''],
];

$GLOBALS['TL_DCA']['tl_page']['fields']['creuvaVariant'] = [
    'inputType' => 'select',
    'options' => DesignSettings::VARIANTS,
    'reference' => &$GLOBALS['TL_LANG']['tl_page']['creuvaVariant_options'],
    'eval' => ['includeBlankOption' => true, 'blankOptionLabel' => &$GLOBALS['TL_LANG']['tl_page']['creuvaFromLayout'], 'tl_class' => 'w50'],
    'sql' => ['type' => 'string', 'length' => 16, 'default' => ''],
];

$GLOBALS['TL_DCA']['tl_page']['fields']['creuvaTypography'] = [
    'inputType' => 'select',
    'options' => DesignSettings::TYPOGRAPHIES,
    'reference' => &$GLOBALS['TL_LANG']['tl_page']['creuvaTypography_options'],
    'eval' => ['includeBlankOption' => true, 'blankOptionLabel' => &$GLOBALS['TL_LANG']['tl_page']['creuvaFromLayout'], 'tl_class' => 'w50'],
    'sql' => ['type' => 'string', 'length' => 16, 'default' => ''],
];

$GLOBALS['TL_DCA']['tl_page']['fields']['creuvaRadius'] = [
    'inputType' => 'select',
    'options' => DesignSettings::RADII,
    'reference' => &$GLOBALS['TL_LANG']['tl_page']['creuvaRadius_options'],
    'eval' => ['includeBlankOption' => true, 'blankOptionLabel' => &$GLOBALS['TL_LANG']['tl_page']['creuvaFromLayout'], 'tl_class' => 'w50 clr'],
    'sql' => ['type' => 'string', 'length' => 16, 'default' => ''],
];
