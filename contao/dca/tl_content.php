<?php

declare(strict_types=1);

/*
 * Creuva theme bundle for Contao Open Source CMS
 *
 * Copyright (C) pdir GmbH <develop@pdir.de>
 */

use Contao\CoreBundle\DataContainer\PaletteManipulator;

$GLOBALS['TL_DCA']['tl_content']['palettes']['creuva_hero'] = '{type_legend},type,creuvaKicker,headline,creuvaHeadlineAccent;{text_legend},text;{image_legend},singleSRC,size,creuvaHeroMask,creuvaBadge;{link_legend},creuvaUrl1,creuvaLinkTitle1,creuvaUrl2,creuvaLinkTitle2;{template_legend:hide},customTpl;{protected_legend:hide},protected;{expert_legend:hide},cssID;{invisible_legend:hide},invisible,start,stop';

// Kicker ("Dachzeile") above the headline of text elements (also used by the
// template variants creuva_media and creuva_stats)
PaletteManipulator::create()
    ->addField('creuvaKicker', 'headline', PaletteManipulator::POSITION_BEFORE)
    ->applyToPalette('text', 'tl_content')
    ->applyToPalette('description_list', 'tl_content')
;

// Text editor with the formats of the theme (accent color, lead, buttons …),
// template contao/templates/backend/be_tinyCreuva.html5
if ('tinyMCE' === ($GLOBALS['TL_DCA']['tl_content']['fields']['text']['eval']['rte'] ?? null)) {
    $GLOBALS['TL_DCA']['tl_content']['fields']['text']['eval']['rte'] = 'tinyCreuva';
}

// Optional icons of the key figures (template variant creuva_stats), in the
// order of the entries
PaletteManipulator::create()
    ->addField('creuvaIcons', 'data', PaletteManipulator::POSITION_AFTER)
    ->applyToPalette('description_list', 'tl_content')
;

$GLOBALS['TL_DCA']['tl_content']['fields']['creuvaIcons'] = [
    'inputType' => 'fileTree',
    'eval' => ['multiple' => true, 'fieldType' => 'checkbox', 'isSortable' => true, 'files' => true, 'filesOnly' => true, 'extensions' => 'svg,png,webp,jpg,jpeg,gif', 'tl_class' => 'clr'],
    'sql' => ['type' => 'blob', 'length' => 65535, 'notnull' => false],
];

$GLOBALS['TL_DCA']['tl_content']['fields']['creuvaKicker'] = [
    'search' => true,
    'inputType' => 'text',
    'eval' => ['maxlength' => 255, 'tl_class' => 'w50 clr'],
    'sql' => ['type' => 'string', 'length' => 255, 'default' => ''],
];

$GLOBALS['TL_DCA']['tl_content']['fields']['creuvaHeadlineAccent'] = [
    'search' => true,
    'inputType' => 'text',
    'eval' => ['maxlength' => 255, 'tl_class' => 'w50'],
    'sql' => ['type' => 'string', 'length' => 255, 'default' => ''],
];

$GLOBALS['TL_DCA']['tl_content']['fields']['creuvaBadge'] = [
    'inputType' => 'textarea',
    'eval' => ['maxlength' => 255, 'tl_class' => 'clr'],
    'sql' => ['type' => 'string', 'length' => 255, 'default' => ''],
];

$GLOBALS['TL_DCA']['tl_content']['fields']['creuvaHeroMask'] = [
    'inputType' => 'select',
    'options' => ['organic', 'rounded', 'none'],
    'reference' => &$GLOBALS['TL_LANG']['tl_content']['creuvaHeroMask_options'],
    'eval' => ['tl_class' => 'w50'],
    'sql' => ['type' => 'string', 'length' => 16, 'default' => 'organic'],
];

foreach ([1, 2] as $i) {
    $GLOBALS['TL_DCA']['tl_content']['fields']['creuvaUrl'.$i] = [
        'inputType' => 'text',
        'eval' => ['rgxp' => 'url', 'maxlength' => 2048, 'dcaPicker' => true, 'tl_class' => 'w50 clr'],
        'sql' => ['type' => 'string', 'length' => 2048, 'default' => ''],
    ];

    $GLOBALS['TL_DCA']['tl_content']['fields']['creuvaLinkTitle'.$i] = [
        'inputType' => 'text',
        'eval' => ['maxlength' => 255, 'tl_class' => 'w50'],
        'sql' => ['type' => 'string', 'length' => 255, 'default' => ''],
    ];
}
