<?php

declare(strict_types=1);

/*
 * Creuva theme bundle for Contao Open Source CMS
 *
 * Copyright (C) pdir GmbH <develop@pdir.dev>
 */

use Contao\CoreBundle\DataContainer\PaletteManipulator;
use Contao\NewsBundle\ContaoNewsBundle;

// The news bundle is optional
if (!class_exists(ContaoNewsBundle::class)) {
    return;
}

// Part of the headline shown in the accent color in the news reader
PaletteManipulator::create()
    ->addField('creuvaHeadlineAccent', 'headline', PaletteManipulator::POSITION_AFTER)
    ->applyToPalette('default', 'tl_news')
;

$GLOBALS['TL_DCA']['tl_news']['fields']['creuvaHeadlineAccent'] = [
    'search' => true,
    'inputType' => 'text',
    'eval' => ['maxlength' => 255, 'tl_class' => 'w50'],
    'sql' => ['type' => 'string', 'length' => 255, 'default' => ''],
];
