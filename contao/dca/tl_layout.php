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
 * Design settings for the "modern" (Twig) layout type. The values are output
 * as CSS custom properties in page/layout/creuva.html.twig.
 */
PaletteManipulator::create()
    ->addLegend('creuva_legend', 'modules_legend', PaletteManipulator::POSITION_AFTER)
    ->addField(['creuvaScheme', 'creuvaVariant', 'creuvaRadius', 'creuvaPrimaryColor', 'creuvaAccentColor', 'creuvaLogoHeight'], 'creuva_legend', PaletteManipulator::POSITION_APPEND)
    ->applyToPalette('modern', 'tl_layout')
;

// Options from config/color-schemes.json, see ColorSchemeOptionsListener; the
// swatches and the live preview come from public/backend.css and backend.js
$GLOBALS['TL_DCA']['tl_layout']['fields']['creuvaScheme'] = [
    'inputType' => 'radio',
    'eval' => ['tl_class' => 'clr creuva-scheme-picker'],
    'sql' => ['type' => 'string', 'length' => 32, 'default' => ''],
];

$GLOBALS['TL_DCA']['tl_layout']['fields']['creuvaVariant'] = [
    'inputType' => 'select',
    'options' => DesignSettings::VARIANTS,
    'reference' => &$GLOBALS['TL_LANG']['tl_layout']['creuvaVariant_options'],
    'eval' => ['tl_class' => 'w50 clr'],
    'sql' => ['type' => 'string', 'length' => 16, 'default' => 'light'],
];

$GLOBALS['TL_DCA']['tl_layout']['fields']['creuvaRadius'] = [
    'inputType' => 'select',
    'options' => DesignSettings::RADII,
    'reference' => &$GLOBALS['TL_LANG']['tl_layout']['creuvaRadius_options'],
    'eval' => ['tl_class' => 'w50'],
    'sql' => ['type' => 'string', 'length' => 16, 'default' => 'large'],
];

$GLOBALS['TL_DCA']['tl_layout']['fields']['creuvaPrimaryColor'] = [
    'inputType' => 'text',
    'eval' => ['maxlength' => 6, 'colorpicker' => true, 'isHexColor' => true, 'tl_class' => 'w50 wizard'],
    'sql' => ['type' => 'string', 'length' => 6, 'default' => ''],
];

$GLOBALS['TL_DCA']['tl_layout']['fields']['creuvaAccentColor'] = [
    'inputType' => 'text',
    'eval' => ['maxlength' => 6, 'colorpicker' => true, 'isHexColor' => true, 'tl_class' => 'w50 wizard'],
    'sql' => ['type' => 'string', 'length' => 6, 'default' => ''],
];

// Height of the logo in the header, e.g. "4rem" (default: 2.25rem)
$GLOBALS['TL_DCA']['tl_layout']['fields']['creuvaLogoHeight'] = [
    'inputType' => 'text',
    'eval' => ['maxlength' => 16, 'rgxp' => 'custom', 'customRgxp' => DesignSettings::LOGO_HEIGHT_PATTERN, 'placeholder' => '2.25rem', 'tl_class' => 'w50 clr'],
    'sql' => ['type' => 'string', 'length' => 16, 'default' => ''],
];
