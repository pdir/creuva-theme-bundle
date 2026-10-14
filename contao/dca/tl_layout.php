<?php

declare(strict_types=1);

/*
 * Creuva theme bundle for Contao Open Source CMS
 *
 * Copyright (C) pdir GmbH <develop@pdir.de>
 */

use Contao\CoreBundle\DataContainer\PaletteManipulator;

/*
 * Design settings for the "modern" (Twig) layout type. The values are output
 * as CSS custom properties in page/layout/creuva.html.twig.
 */
PaletteManipulator::create()
    ->addLegend('creuva_legend', 'modules_legend', PaletteManipulator::POSITION_AFTER)
    ->addField(['creuvaVariant', 'creuvaRadius', 'creuvaPrimaryColor', 'creuvaAccentColor'], 'creuva_legend', PaletteManipulator::POSITION_APPEND)
    ->applyToPalette('modern', 'tl_layout')
;

$GLOBALS['TL_DCA']['tl_layout']['fields']['creuvaVariant'] = [
    'inputType' => 'select',
    'options' => ['light', 'dark', 'editorial', 'corporate'],
    'reference' => &$GLOBALS['TL_LANG']['tl_layout']['creuvaVariant_options'],
    'eval' => ['tl_class' => 'w50'],
    'sql' => ['type' => 'string', 'length' => 16, 'default' => 'light'],
];

$GLOBALS['TL_DCA']['tl_layout']['fields']['creuvaRadius'] = [
    'inputType' => 'select',
    'options' => ['none', 'small', 'medium', 'large'],
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
