<?php

declare(strict_types=1);

/*
 * Creuva theme bundle for Contao Open Source CMS
 *
 * Copyright (C) pdir GmbH <develop@pdir.de>
 */

use Contao\CoreBundle\DataContainer\PaletteManipulator;

$GLOBALS['TL_DCA']['tl_content']['palettes']['creuva_hero'] = '{type_legend},type,creuvaKicker,headline,creuvaHeadlineAccent;{text_legend},text;{image_legend},singleSRC,size,creuvaHeroMask,creuvaBadge;{link_legend},creuvaUrl1,creuvaLinkTitle1,creuvaUrl2,creuvaLinkTitle2;{template_legend:hide},customTpl;{protected_legend:hide},protected;{expert_legend:hide},cssID;{invisible_legend:hide},invisible,start,stop';

// Logo bar (images from multiSRC, link from the file metadata)
$GLOBALS['TL_DCA']['tl_content']['palettes']['creuva_logos'] = '{type_legend},type,headline;{source_legend},multiSRC,size;{creuva_logos_legend},creuvaLogosHeight;{template_legend:hide},customTpl;{protected_legend:hide},protected;{expert_legend:hide},cssID;{invisible_legend:hide},invisible,start,stop';

// Height of the logos, e.g. "8rem" (default: 5rem); the width may be four times the height
$GLOBALS['TL_DCA']['tl_content']['fields']['creuvaLogosHeight'] = [
    'inputType' => 'text',
    'eval' => ['maxlength' => 16, 'rgxp' => 'custom', 'customRgxp' => Pdir\CreuvaThemeBundle\Design\DesignSettings::LOGO_HEIGHT_PATTERN, 'placeholder' => '5rem', 'tl_class' => 'w50'],
    'sql' => ['type' => 'string', 'length' => 16, 'default' => ''],
];

// Match of two teams (next match or result); the competition is the kicker
$GLOBALS['TL_DCA']['tl_content']['palettes']['creuva_match'] = '{type_legend},type,creuvaKicker,headline;{creuva_match_legend},creuvaHomeTeam,creuvaHomeLogo,creuvaAwayTeam,creuvaAwayLogo,size,creuvaMatchDate,creuvaVenue,creuvaScore;{link_legend},creuvaUrl1,creuvaLinkTitle1;{template_legend:hide},customTpl;{protected_legend:hide},protected;{expert_legend:hide},cssID;{invisible_legend:hide},invisible,start,stop';

foreach (['Home', 'Away'] as $side) {
    $GLOBALS['TL_DCA']['tl_content']['fields']['creuva'.$side.'Team'] = [
        'inputType' => 'text',
        'eval' => ['maxlength' => 128, 'tl_class' => 'w50 clr'],
        'sql' => ['type' => 'string', 'length' => 128, 'default' => ''],
    ];

    $GLOBALS['TL_DCA']['tl_content']['fields']['creuva'.$side.'Logo'] = [
        'inputType' => 'fileTree',
        'eval' => ['fieldType' => 'radio', 'filesOnly' => true, 'extensions' => '%contao.image.valid_extensions%', 'tl_class' => 'w50'],
        'sql' => ['type' => 'binary', 'length' => 16, 'fixed' => true, 'notnull' => false],
    ];
}

$GLOBALS['TL_DCA']['tl_content']['fields']['creuvaMatchDate'] = [
    'inputType' => 'text',
    'eval' => ['rgxp' => 'datim', 'datepicker' => true, 'tl_class' => 'w50 wizard clr'],
    'sql' => ['type' => 'integer', 'unsigned' => true, 'notnull' => false],
];

$GLOBALS['TL_DCA']['tl_content']['fields']['creuvaVenue'] = [
    'inputType' => 'text',
    'eval' => ['maxlength' => 255, 'tl_class' => 'w50'],
    'sql' => ['type' => 'string', 'length' => 255, 'default' => ''],
];

$GLOBALS['TL_DCA']['tl_content']['fields']['creuvaScore'] = [
    'inputType' => 'text',
    'eval' => ['maxlength' => 32, 'tl_class' => 'w50'],
    'sql' => ['type' => 'string', 'length' => 32, 'default' => ''],
];

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
    'options' => ['organic', 'rounded', 'none', 'cover'],
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

// Content slider: elements per view, arrows, dots and thumbnails
PaletteManipulator::create()
    ->addField(['creuvaSliderView', 'creuvaSliderArrows', 'creuvaSliderDots', 'creuvaSliderThumbs'], 'sliderContinuous', PaletteManipulator::POSITION_AFTER)
    ->applyToPalette('swiper', 'tl_content')
;

$GLOBALS['TL_DCA']['tl_content']['fields']['creuvaSliderView'] = [
    'inputType' => 'select',
    'options' => ['1', '2', '3', '4'],
    'reference' => &$GLOBALS['TL_LANG']['tl_content']['creuvaSliderView_options'],
    'eval' => ['tl_class' => 'w50 clr'],
    'sql' => ['type' => 'string', 'length' => 1, 'fixed' => true, 'default' => '1'],
];

foreach (['creuvaSliderArrows' => '1', 'creuvaSliderDots' => '1', 'creuvaSliderThumbs' => ''] as $field => $default) {
    $GLOBALS['TL_DCA']['tl_content']['fields'][$field] = [
        'inputType' => 'checkbox',
        'eval' => ['tl_class' => 'w50 m12'],
        'sql' => ['type' => 'string', 'length' => 1, 'fixed' => true, 'default' => $default],
    ];
}
