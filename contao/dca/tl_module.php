<?php

declare(strict_types=1);

/*
 * Creuva theme bundle for Contao Open Source CMS
 *
 * Copyright (C) pdir GmbH <develop@pdir.de>
 */

$GLOBALS['TL_DCA']['tl_module']['palettes']['creuva_header'] = '{title_legend},name,type;{creuva_logo_legend},creuvaLogo,imgSize,creuvaLogoText;{nav_legend},creuvaNavigation;{link_legend},creuvaUrl1,creuvaLinkTitle1;{creuva_search_legend},creuvaSearch;{template_legend:hide},customTpl;{protected_legend:hide},protected;{expert_legend:hide},cssID';
$GLOBALS['TL_DCA']['tl_module']['palettes']['creuva_style_switcher'] = '{title_legend},name,type;{config_legend},creuvaPreviewOnly;{template_legend:hide},customTpl;{protected_legend:hide},protected;{expert_legend:hide},cssID';
$GLOBALS['TL_DCA']['tl_module']['palettes']['creuva_brand'] = '{title_legend},name,headline,type;{creuva_logo_legend},creuvaLogo,imgSize,creuvaLogoText;{text_legend},creuvaClaim;{creuva_social_legend},creuvaSocialLinks;{template_legend:hide},customTpl;{protected_legend:hide},protected;{expert_legend:hide},cssID';

$GLOBALS['TL_DCA']['tl_module']['fields']['creuvaLogo'] = [
    'inputType' => 'fileTree',
    'eval' => ['fieldType' => 'radio', 'filesOnly' => true, 'extensions' => 'svg,png,jpg,jpeg,webp,avif', 'tl_class' => 'clr'],
    'sql' => ['type' => 'binary', 'length' => 16, 'fixed' => true, 'notnull' => false],
];

$GLOBALS['TL_DCA']['tl_module']['fields']['creuvaLogoText'] = [
    'inputType' => 'text',
    'eval' => ['maxlength' => 255, 'tl_class' => 'w50'],
    'sql' => ['type' => 'string', 'length' => 255, 'default' => ''],
];

$GLOBALS['TL_DCA']['tl_module']['fields']['creuvaNavigation'] = [
    'inputType' => 'select',
    'eval' => ['includeBlankOption' => true, 'chosen' => true, 'tl_class' => 'w50'],
    'sql' => ['type' => 'integer', 'unsigned' => true, 'default' => 0],
];

$GLOBALS['TL_DCA']['tl_module']['fields']['creuvaUrl1'] = [
    'inputType' => 'text',
    'eval' => ['rgxp' => 'url', 'maxlength' => 2048, 'dcaPicker' => true, 'tl_class' => 'w50 clr'],
    'sql' => ['type' => 'string', 'length' => 2048, 'default' => ''],
];

$GLOBALS['TL_DCA']['tl_module']['fields']['creuvaLinkTitle1'] = [
    'inputType' => 'text',
    'eval' => ['maxlength' => 255, 'tl_class' => 'w50'],
    'sql' => ['type' => 'string', 'length' => 255, 'default' => ''],
];

// Search icon in the header: opens a search field that sends the keywords to
// the page with the search module (the icon is hidden without the checkbox)
$GLOBALS['TL_DCA']['tl_module']['palettes']['__selector__'][] = 'creuvaSearch';
$GLOBALS['TL_DCA']['tl_module']['subpalettes']['creuvaSearch'] = 'creuvaSearchPage';

$GLOBALS['TL_DCA']['tl_module']['fields']['creuvaSearch'] = [
    'inputType' => 'checkbox',
    'eval' => ['submitOnChange' => true, 'tl_class' => 'clr'],
    'sql' => ['type' => 'boolean', 'default' => false],
];

$GLOBALS['TL_DCA']['tl_module']['fields']['creuvaSearchPage'] = [
    'inputType' => 'pageTree',
    'foreignKey' => 'tl_page.title',
    'eval' => ['fieldType' => 'radio', 'mandatory' => true, 'tl_class' => 'clr'],
    'sql' => ['type' => 'integer', 'unsigned' => true, 'default' => 0],
    'relation' => ['type' => 'hasOne', 'load' => 'lazy'],
];

$GLOBALS['TL_DCA']['tl_module']['fields']['creuvaClaim'] = [
    'inputType' => 'textarea',
    'eval' => ['maxlength' => 500, 'tl_class' => 'clr'],
    'sql' => ['type' => 'text', 'notnull' => false],
];

$GLOBALS['TL_DCA']['tl_module']['fields']['creuvaSocialLinks'] = [
    'inputType' => 'textarea',
    'eval' => ['tl_class' => 'clr'],
    'sql' => ['type' => 'text', 'notnull' => false],
];

$GLOBALS['TL_DCA']['tl_module']['fields']['creuvaPreviewOnly'] = [
    'inputType' => 'checkbox',
    'eval' => ['tl_class' => 'w50'],
    'sql' => ['type' => 'boolean', 'default' => false],
];

// Logo bar (images from multiSRC, link from the file metadata)
$GLOBALS['TL_DCA']['tl_module']['palettes']['creuva_logos'] = '{title_legend},name,headline,type;{source_legend},multiSRC,imgSize;{creuva_logos_legend},creuvaLogosVariant,creuvaLogosHeight;{template_legend:hide},customTpl;{protected_legend:hide},protected;{expert_legend:hide},cssID';

$GLOBALS['TL_DCA']['tl_module']['fields']['creuvaLogosVariant'] = [
    'inputType' => 'select',
    'options' => Pdir\CreuvaThemeBundle\Controller\FrontendModule\LogosController::VARIANTS,
    'reference' => &$GLOBALS['TL_LANG']['tl_module']['creuvaLogosVariant_options'],
    'eval' => ['tl_class' => 'w50'],
    'sql' => ['type' => 'string', 'length' => 16, 'default' => 'surface'],
];

// Height of the logos, e.g. "8rem" (default: 5rem); the width may be twice the height
$GLOBALS['TL_DCA']['tl_module']['fields']['creuvaLogosHeight'] = [
    'inputType' => 'text',
    'eval' => ['maxlength' => 16, 'rgxp' => 'custom', 'customRgxp' => Pdir\CreuvaThemeBundle\Design\DesignSettings::LOGO_HEIGHT_PATTERN, 'placeholder' => '5rem', 'tl_class' => 'w50'],
    'sql' => ['type' => 'string', 'length' => 16, 'default' => ''],
];
