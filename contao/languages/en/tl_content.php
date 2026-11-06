<?php

declare(strict_types=1);

/*
 * Creuva theme bundle for Contao Open Source CMS
 *
 * Copyright (C) pdir GmbH <develop@pdir.de>
 */

$GLOBALS['TL_LANG']['tl_content']['creuvaKicker'] = ['Kicker', 'Short text above the headline. Can be combined with a two-tone headline.'];

// Fields of the theme components bundle, named after their effect in Creuva
$GLOBALS['TL_LANG']['tl_content']['subline'] = ['Headline addition', 'Without a checkbox: small line above the headline. "Two-tone headline": addition and headline in one line, the addition in the normal color, the headline in the accent color – e.g. addition "More than just an", headline "agency.". "Below": line below the headline.'];
$GLOBALS['TL_LANG']['tl_content']['headline_inline'] = ['Two-tone headline', 'Addition and headline side by side, the headline in the accent color.'];
$GLOBALS['TL_LANG']['tl_content']['subheadline'] = ['Show addition below', 'Show the addition as a line below the headline.'];
$GLOBALS['TL_LANG']['tl_content']['creuvaIcons'] = ['Icons (key figures)', 'Optional icons above the figures of the template variant "Key figures", in the order of the entries (e.g. single-colored SVG icons).'];
$GLOBALS['TL_LANG']['tl_content']['creuvaBadge'] = ['Badge text', 'Small card on the image, one line per point (with check icon), e.g. "Creative", "Strategic", "Future-proof".'];
$GLOBALS['TL_LANG']['tl_content']['creuvaHeroMask'] = ['Image shape', 'Organic: image in 4:3 with an organic mask. Rounded: the whole image with rounded corners, the accent shape behind it. Image unchanged: no shape, background shape or cropping. Image unchanged with accent shape: no shadow or cropping, the accent shape behind it (ideal for cut-out images with a transparent background). Full screen: the image fills the whole section with light text on top (choose a landscape image size, the badge text is shown as a caption).'];
$GLOBALS['TL_LANG']['tl_content']['creuvaHeroMask_options'] = ['organic' => 'Organic', 'rounded' => 'Rounded', 'none' => 'Image unchanged', 'blob' => 'Image unchanged with accent shape', 'cover' => 'Full screen (text on the image)'];
$GLOBALS['TL_LANG']['tl_content']['creuvaUrl1'] = ['Primary button link', 'Target of the first button.'];
$GLOBALS['TL_LANG']['tl_content']['creuvaLinkTitle1'] = ['Primary button text', 'Label of the first button.'];
$GLOBALS['TL_LANG']['tl_content']['creuvaUrl2'] = ['Secondary button link', 'Target of the second button.'];
$GLOBALS['TL_LANG']['tl_content']['creuvaLinkTitle2'] = ['Secondary button text', 'Label of the second button.'];
$GLOBALS['TL_LANG']['tl_content']['creuvaHeadlineAccent'] = ['Highlighted part', 'Appended to the headline in the accent color.'];
$GLOBALS['TL_LANG']['tl_content']['creuvaSliderView'] = ['Elements per view', 'How many elements are visible side by side (at most two on tablets, one on phones).'];
$GLOBALS['TL_LANG']['tl_content']['creuvaSliderView_options'] = ['1' => '1 element', '2' => '2 elements', '3' => '3 elements', '4' => '4 elements'];
$GLOBALS['TL_LANG']['tl_content']['creuvaSliderArrows'] = ['Navigation arrows', 'Show arrows below the slider.'];
$GLOBALS['TL_LANG']['tl_content']['creuvaSliderDots'] = ['Dots', 'Show one dot per element below the slider.'];
$GLOBALS['TL_LANG']['tl_content']['creuvaSliderThumbs'] = ['Thumbnails', 'Show preview images below the slider (from the first image of each element), e.g. for an image gallery.'];
$GLOBALS['TL_LANG']['tl_content']['creuva_logos_legend'] = 'Appearance';
$GLOBALS['TL_LANG']['tl_content']['creuvaLogosHeight'] = ['Logo height', 'Height of the logos as a CSS value, e.g. "4rem" (default: 5rem). The width may be four times the height (word marks); choose a large enough image size.'];
$GLOBALS['TL_LANG']['tl_content']['creuva_match_legend'] = 'Match';
$GLOBALS['TL_LANG']['tl_content']['creuvaHomeTeam'] = ['Home team', 'Name of the home team.'];
$GLOBALS['TL_LANG']['tl_content']['creuvaHomeLogo'] = ['Home crest', 'Crest or logo of the home team (SVG recommended).'];
$GLOBALS['TL_LANG']['tl_content']['creuvaAwayTeam'] = ['Away team', 'Name of the away team.'];
$GLOBALS['TL_LANG']['tl_content']['creuvaAwayLogo'] = ['Away crest', 'Crest or logo of the away team (SVG recommended).'];
$GLOBALS['TL_LANG']['tl_content']['creuvaMatchDate'] = ['Kick-off', 'Date and time of the match.'];
$GLOBALS['TL_LANG']['tl_content']['creuvaVenue'] = ['Venue', 'E.g. "Mühlbach sports park, grass pitch".'];
$GLOBALS['TL_LANG']['tl_content']['creuvaScore'] = ['Result', 'E.g. "3:1". Leave empty for an upcoming match, the kick-off time is shown then. Text like "cancelled" is possible, too.'];
