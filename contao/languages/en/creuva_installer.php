<?php

declare(strict_types=1);

/*
 * Creuva theme bundle for Contao Open Source CMS
 *
 * Copyright (C) pdir GmbH <develop@pdir.de>
 */

$GLOBALS['TL_LANG']['creuva_installer'] = [
    'intro' => 'Set up the theme and import demos here. Every action corresponds to a console command, both ways lead to the same result. Adjust the color scheme, color mode and typography in the page layout afterwards.',
    'missing_columns' => 'The database is not up to date (missing: %s). Please update the database in the Contao Manager or run "contao:migrate" first.',
    'success' => 'Done.',
    'error' => 'The action failed. See the output for details.',
    'command' => 'Corresponds to the console command',
    'output' => 'Output',
    'install_legend' => 'Set up the theme',
    'mode' => ['What should be set up?', ''],
    'mode_demo' => ['Import a demo website', 'New website root with all pages, contents, news, images and forms of a demo.'],
    'mode_pages' => ['Create an empty site structure', 'New website root with start, contact, imprint, privacy, sitemap and search pages.'],
    'mode_roots' => ['Use an existing website', 'The theme is assigned to the selected website roots, the contents are not changed.'],
    'mode_theme' => ['Only create the theme', 'Create the theme, modules and page layout and assign the layout yourself later.'],
    'demo' => ['Demo', 'One of the included demos.'],
    'demo_folder' => ['Or own folder', 'Folder with a demo.yaml file in the file manager, e.g. "files/my-demo". Takes precedence over the selection.'],
    'roots' => ['Website roots', 'These websites use the Creuva layout afterwards. An existing layout is replaced.'],
    'no_roots' => 'There is no website root yet.',
    'site_title' => ['Website title', 'Page title of the new website root.'],
    'language' => ['Language', 'Language of the new website root, e.g. "de" or "en".'],
    'dns' => ['Domain', 'Optional. Required if there already is a website root with the same language and no domain.'],
    'theme_name' => ['Theme name', 'A theme with this name must not exist yet.'],
    'reset' => ['Delete all contents first', 'Empties the site structure, articles, themes, modules, forms, news etc., e.g. to import a demo again. Users, members and files are kept.'],
    'no_backup' => ['Without database backup', 'By default, a backup is created before deleting (restore with "contao:backup:restore").'],
    'install_submit' => 'Set up',
    'styleguide_legend' => 'Styleguide',
    'styleguide_intro' => 'Adds the hidden page "Styleguide" with all elements, template variants, CSS classes and modules to a website.',
    'styleguide_root' => ['Website', 'Website root that uses the Creuva layout.'],
    'styleguide_replace' => ['Replace an existing styleguide', 'For example after an update of the theme.'],
    'styleguide_submit' => 'Add styleguide',
    'css_styles_legend' => 'CSS styles',
    'css_styles_intro' => 'Adds missing Creuva groups and styles to the CSS style selector, e.g. after an update with new classes. Own changes are kept.',
    'css_styles_submit' => 'Add CSS styles',
    'running' => 'Please wait …',
];
