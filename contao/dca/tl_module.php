<?php

declare(strict_types=1);

/*
 * Creuva theme bundle for Contao Open Source CMS
 *
 * Copyright (C) pdir GmbH <develop@pdir.de>
 */

$GLOBALS['TL_DCA']['tl_module']['palettes']['creuva_header'] = '{title_legend},name,type;{creuva_logo_legend},creuvaLogo,imgSize,creuvaLogoText;{nav_legend},creuvaNavigation;{link_legend},creuvaUrl1,creuvaLinkTitle1;{template_legend:hide},customTpl;{protected_legend:hide},protected;{expert_legend:hide},cssID';
$GLOBALS['TL_DCA']['tl_module']['palettes']['creuva_style_switcher'] = '{title_legend},name,type;{template_legend:hide},customTpl;{protected_legend:hide},protected;{expert_legend:hide},cssID';
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
