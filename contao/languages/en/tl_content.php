<?php

declare(strict_types=1);

/*
 * Creuva theme bundle for Contao Open Source CMS
 *
 * Copyright (C) pdir GmbH <develop@pdir.dev>
 */

$GLOBALS['TL_LANG']['tl_content']['creuvaKicker'] = ['Kicker', 'Short text above the headline. Can be combined with a two-tone headline.'];

// Fields of the theme components bundle, named after their effect in Creuva
$GLOBALS['TL_LANG']['tl_content']['subline'] = ['Headline addition', 'Without a checkbox: small line above the headline. "Two-tone headline": addition and headline in one line, the addition in the normal color, the headline in the accent color – e.g. addition "More than just an", headline "agency.". "Below": line below the headline.'];
$GLOBALS['TL_LANG']['tl_content']['headline_inline'] = ['Two-tone headline', 'Addition and headline side by side, the headline in the accent color.'];
$GLOBALS['TL_LANG']['tl_content']['subheadline'] = ['Show addition below', 'Show the addition as a line below the headline.'];
$GLOBALS['TL_LANG']['tl_content']['creuvaIcons'] = ['Icons (key figures)', 'Optional icons above the figures of the template variant "Key figures", in the order of the entries (e.g. single-colored SVG icons).'];
$GLOBALS['TL_LANG']['tl_content']['creuva_badge_legend'] = 'Badge';
$GLOBALS['TL_LANG']['tl_content']['creuvaBadge'] = ['Badge text', 'Small card on the image. List: one line per point, e.g. "Creative", "Strategic", "Future-proof"; a "!" at the beginning of a line shows an exclamation mark instead of the icon (note). Info: first line bold, further lines small below. Label: first line small, second line bold. Text: all lines as normal text. Key figure: first line as a large number, e.g. "250+", the label below. Rating: the first line is the value, e.g. "4.9" (fills the stars), the source below. With "Full screen" the text is shown as a caption.'];
$GLOBALS['TL_LANG']['tl_content']['creuvaBadgeStyle'] = ['Appearance', 'List: points with an icon (former appearance). Checklist: icons in a circle. Info: large icon, bold line, text below (e.g. location, documentation). Label: large icon, small line, bold line below (e.g. "Usually replies" / "within 24 hours"). Text: normal text, one paragraph per line, optionally with an icon and a headline (e.g. a short quote or a note). Key figure: large number in the accent color with a label (e.g. "250+" / "projects delivered"). Rating: five stars filled according to the value of the first line, the value next to them, the source below (e.g. "4.9" / "from 120 Google reviews").'];
$GLOBALS['TL_LANG']['tl_content']['creuvaBadgeStyle_options'] = ['list' => 'List', 'checklist' => 'Checklist (icons in a circle)', 'info' => 'Info (icon, title, text)', 'label' => 'Label (icon, note, value)', 'text' => 'Text', 'stat' => 'Key figure (number, label)', 'rating' => 'Rating (stars, value, source)'];
$GLOBALS['TL_LANG']['tl_content']['creuvaBadgePosition'] = ['Position', 'Place at the edge of the image the badge is placed at. On large screens it overhangs the image sideways (left/right) or downwards/upwards (center).'];
$GLOBALS['TL_LANG']['tl_content']['creuvaBadgePosition_options'] = ['bottom-start' => 'Bottom left', 'bottom-center' => 'Bottom center', 'bottom-end' => 'Bottom right', 'center-start' => 'Middle left', 'center-end' => 'Middle right', 'top-start' => 'Top left', 'top-center' => 'Top center', 'top-end' => 'Top right'];
$GLOBALS['TL_LANG']['tl_content']['creuvaBadgeHeadline'] = ['Badge headline', 'Optional small uppercase line above the text, e.g. "Contao partner" or "Result".'];
$GLOBALS['TL_LANG']['tl_content']['creuvaBadgeIcon'] = ['Icon', 'List and checklist: icon in front of each point (default: check). All other appearances: large icon on the left (default: none).'];
$GLOBALS['TL_LANG']['tl_content']['creuvaBadgeIcon_options'] = ['check' => 'Check', 'clock' => 'Clock', 'pin' => 'Location', 'file' => 'Document', 'info' => 'Info', 'chat' => 'Speech bubble', 'download' => 'Download', 'shield' => 'Shield', 'star' => 'Star', 'mail' => 'Letter', 'phone' => 'Phone'];
$GLOBALS['TL_LANG']['tl_content']['creuvaBadgeUrl'] = ['Badge link', 'Optional: the whole badge becomes a link, e.g. to the contact page or the documentation.'];
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
