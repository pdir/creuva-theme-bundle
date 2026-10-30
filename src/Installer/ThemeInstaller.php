<?php

declare(strict_types=1);

/*
 * Creuva theme bundle for Contao Open Source CMS
 *
 * Copyright (C) pdir GmbH <develop@pdir.de>
 */

namespace Pdir\CreuvaThemeBundle\Installer;

use Doctrine\DBAL\ArrayParameterType;
use Doctrine\DBAL\Connection;
use Pdir\CreuvaThemeBundle\Design\DesignSettings;
use Symfony\Component\Yaml\Exception\ParseException;

/**
 * Creates the Creuva theme with its front end modules and page layout and
 * optionally a basic page structure (root page, start page, contact and
 * legal pages).
 */
class ThemeInstaller
{
    private const TEXTS = [
        'de' => [
            'home' => 'Startseite',
            'contact' => 'Kontakt',
            'imprint' => 'Impressum',
            'privacy' => 'Datenschutz',
            'sitemap' => 'Sitemap',
            'search' => 'Suche',
            'mainNavigation' => 'Hauptnavigation',
            'header' => 'Header',
            'brand' => 'Footer – Marke',
            'footerPages' => 'Footer – Seiten',
            'footerLegal' => 'Footer – Rechtliches',
            'copyright' => 'Copyright',
            'bottomLinks' => 'Untere Links',
            'styleSwitcher' => 'Style-Switcher (Demo)',
            'legal' => 'Rechtliches',
            'pages' => 'Seiten',
            'layout' => 'Creuva Standard',
            'claim' => "Creuva – create something new.\nDigitale Lösungen mit Contao.",
            'rights' => 'Alle Rechte vorbehalten.',
            'cta' => 'Kontakt aufnehmen',
            'heroKicker' => 'Willkommen',
            'heroHeadline' => 'Ihre neue Website mit',
            'heroText' => '<p>Diese Startseite wurde von Creuva angelegt. Bearbeiten Sie die Inhalte im Backend unter <strong>Inhalte › Artikel</strong>.</p>',
            'contactText' => '<p>Hier können Sie ein Kontaktformular einfügen.</p>',
            'placeholder' => '<p>Bitte ergänzen Sie hier Ihre Angaben.</p>',
        ],
        'en' => [
            'home' => 'Home',
            'contact' => 'Contact',
            'imprint' => 'Imprint',
            'privacy' => 'Privacy policy',
            'sitemap' => 'Sitemap',
            'search' => 'Search',
            'mainNavigation' => 'Main navigation',
            'header' => 'Header',
            'brand' => 'Footer – brand',
            'footerPages' => 'Footer – pages',
            'footerLegal' => 'Footer – legal',
            'copyright' => 'Copyright',
            'bottomLinks' => 'Bottom links',
            'styleSwitcher' => 'Style switcher (demo)',
            'legal' => 'Legal',
            'pages' => 'Pages',
            'layout' => 'Creuva default',
            'claim' => "Creuva – create something new.\nDigital solutions with Contao.",
            'rights' => 'All rights reserved.',
            'cta' => 'Get in touch',
            'heroKicker' => 'Welcome',
            'heroHeadline' => 'Your new website with',
            'heroText' => '<p>This start page was created by Creuva. Edit the content in the back end under <strong>Content › Articles</strong>.</p>',
            'contactText' => '<p>Add a contact form here.</p>',
            'placeholder' => '<p>Please add your details here.</p>',
        ],
    ];

    /**
     * Image size of the logo in the header and brand module, unless the demo
     * defines an image size with the key "logo". The logo is shown with a height
     * of 2.25rem by default (see creuvaLogoHeight), the box leaves room for
     * larger logo heights and high resolution displays.
     */
    public const LOGO_SIZE = [
        'name' => 'Logo',
        'width' => 480,
        'height' => 120,
        'resizeMode' => 'box',
        'densities' => '2x',
        'skipIfDimensionsMatch' => true,
    ];

    /**
     * Aliases used to find existing pages in a site structure.
     */
    private const ALIASES = [
        'contact' => ['kontakt', 'contact', 'kontaktformular'],
        'imprint' => ['impressum', 'imprint', 'legal-notice'],
        'privacy' => ['datenschutz', 'datenschutzerklaerung', 'privacy', 'privacy-policy'],
        'sitemap' => ['sitemap'],
        'search' => ['suche', 'search', 'suchergebnisse', 'search-results'],
    ];

    public function __construct(
        private readonly Connection $connection,
        private readonly DemoImporter $demoImporter,
        private readonly StyleguideInstaller $styleguideInstaller,
        private readonly CssStyleImporter $cssStyleImporter,
    ) {
    }

    /**
     * @return array<string, string> demo name => title
     */
    public function getAvailableDemos(): array
    {
        return $this->demoImporter->getAvailableDemos();
    }

    /**
     * Loads the demo definition to check it before the installation.
     *
     * @throws \InvalidArgumentException if the demo does not exist
     * @throws ParseException             if the demo.yaml file is invalid
     */
    public function checkDemo(string $demo): void
    {
        $definition = $this->demoImporter->load($demo);

        if (!\is_array($definition) || !\is_array($definition['pages'] ?? null)) {
            throw new \InvalidArgumentException(\sprintf('The demo.yaml file of the demo "%s" does not define any pages.', $demo));
        }
    }

    /**
     * Returns the names of missing database columns (empty if the schema is up to date).
     *
     * @return list<string>
     */
    public function getMissingColumns(): array
    {
        $required = [
            'tl_layout' => ['creuvavariant', 'creuvatypography', 'creuvascheme', 'creuvaradius', 'creuvalogoheight'],
            'tl_module' => ['creuvanavigation', 'creuvasociallinks', 'creuvasearch', 'creuvasearchpage'],
            'tl_page' => ['creuvascheme', 'creuvavariant', 'creuvatypography', 'creuvaradius'],
            'tl_content' => ['creuvaheadlineaccent'],
        ];

        $missing = [];
        $schemaManager = $this->connection->createSchemaManager();

        foreach ($required as $table => $columns) {
            $existing = array_map(strtolower(...), array_keys($schemaManager->listTableColumns($table)));

            foreach ($columns as $column) {
                if (!\in_array($column, $existing, true)) {
                    $missing[] = $table.'.'.$column;
                }
            }
        }

        return $missing;
    }

    public function themeExists(string $name): bool
    {
        return false !== $this->connection->fetchOne('SELECT id FROM tl_theme WHERE name = ?', [$name]);
    }

    /**
     * @return array<int, array{id: int, title: string, dns: string, language: string, layout: int}>
     */
    public function getRootPages(): array
    {
        $rows = $this->connection->fetchAllAssociative(
            "SELECT id, title, dns, language, layout FROM tl_page WHERE type IN ('root', 'rootfallback') ORDER BY sorting",
        );

        $pages = [];

        foreach ($rows as $row) {
            $pages[(int) $row['id']] = [
                'id' => (int) $row['id'],
                'title' => (string) $row['title'],
                'dns' => (string) $row['dns'],
                'language' => (string) $row['language'],
                'layout' => (int) $row['layout'],
            ];
        }

        return $pages;
    }

    /**
     * @param list<int> $rootIds existing root pages that should use the new layout
     */
    public function install(InstallOptions $options, array $rootIds = []): InstallResult
    {
        return $this->connection->transactional(function () use ($options, $rootIds): InstallResult {
            $result = new InstallResult();
            $texts = self::TEXTS[$this->textLanguage($options->language)];

            // CSS classes as styles in the CSS style selector (editable in the back end)
            $this->cssStyleImporter->import();

            $result->themeId = $this->insert('tl_theme', [
                'name' => $options->themeName,
                'author' => 'pdir GmbH',
            ]);

            // Pages: import a demo, create a new site structure or look up existing pages
            $pageIds = [];
            $demo = null;

            if (null !== $options->demo) {
                $demo = $this->demoImporter->load($options->demo);
                $this->demoImporter->prepare($options->demo, $demo, $options->language);
                $result->imageSizes = $this->demoImporter->importImageSizes($demo, $result->themeId);

                $rootId = $this->createRootPage($options);
                $rootIds = [$rootId];

                $this->demoImporter->configureRootPage($demo, $rootId);
                $result->pages = $this->demoImporter->importPages($demo, $rootId);
                $pageIds = $this->findPages($rootId) + array_filter(['home' => $result->pages['index'] ?? null]);
            } elseif ($options->createPages) {
                $rootId = $this->createPages($options, $texts, $result);
                $rootIds = [$rootId];
                $pageIds = $result->pages;
            } elseif (1 === \count($rootIds)) {
                $pageIds = $this->findPages($rootIds[0]);
            }

            // Own image size for the logo of the header and brand module
            if (!isset($result->imageSizes['logo'])) {
                $result->imageSizes['logo'] = $this->demoImporter->importImageSize(self::LOGO_SIZE, $result->themeId);
            }

            $logoSize = ['imgSize' => serialize(['', '', (string) $result->imageSizes['logo']])];

            // Front end modules
            $modules = [];

            $modules['navigation'] = $this->insertModule($result, [
                'name' => $texts['mainNavigation'],
                'type' => 'navigation',
                'levelOffset' => 0,
                'showLevel' => 2,
            ]);

            $header = [
                'name' => $texts['header'],
                'type' => 'creuva_header',
                'creuvaNavigation' => $modules['navigation'],
            ];

            if (isset($demo['header']['cta'])) {
                $header['creuvaUrl1'] = '{{link_url::'.($result->pages[$demo['header']['cta']['page']] ?? $demo['header']['cta']['page']).'}}';
                $header['creuvaLinkTitle1'] = $demo['header']['cta']['title'];
            } elseif (isset($pageIds['contact'])) {
                $header['creuvaUrl1'] = '{{link_url::'.$pageIds['contact'].'}}';
                $header['creuvaLinkTitle1'] = $texts['cta'];
            }

            // Search in the header, the results appear on the search page ("header: {search: suche}")
            $searchPage = isset($demo['header']['search']) ? ($result->pages[$demo['header']['search']] ?? null) : ($pageIds['search'] ?? null);

            if (null !== $searchPage) {
                $header['creuvaSearch'] = 1;
                $header['creuvaSearchPage'] = $searchPage;
            }

            $modules['header'] = $this->insertModule($result, $header + $this->getDemoLogo($demo['header'] ?? []) + $logoSize);

            $modules['brand'] = $this->insertModule($result, [
                'name' => $texts['brand'],
                'type' => 'creuva_brand',
                'creuvaClaim' => $demo['brand']['claim'] ?? $texts['claim'],
                'creuvaSocialLinks' => implode("\n", $demo['brand']['social'] ?? []),
            ] + $this->getDemoLogo($demo['brand'] ?? []) + $logoSize);

            $footerSlots = ['brand'];

            // Footer columns of the demo
            $footerColumns = [];

            foreach ($demo['footer'] ?? [] as $i => $column) {
                $pages = array_values(array_filter(array_map(fn (string $alias) => $result->pages[$alias] ?? null, $column['pages'])));
                $footerColumns['footer'.$i] = $column['pages'];

                $modules['footer'.$i] = $this->insertModule($result, [
                    'name' => 'Footer – '.$column['headline'],
                    'type' => 'customnav',
                    'headline' => serialize(['unit' => 'h3', 'value' => $column['headline']]),
                    'pages' => serialize(array_map('strval', $pages)),
                ]);

                $footerSlots[] = 'footer'.$i;
            }

            $footerPages = array_values(array_filter([$pageIds['home'] ?? null, $pageIds['contact'] ?? null]));

            if ($footerPages && !$demo) {
                $modules['footerPages'] = $this->insertModule($result, [
                    'name' => $texts['footerPages'],
                    'type' => 'customnav',
                    'headline' => serialize(['unit' => 'h3', 'value' => $texts['pages']]),
                    'pages' => serialize(array_map('strval', $footerPages)),
                ]);
            }

            $legalPages = array_values(array_filter([$pageIds['imprint'] ?? null, $pageIds['privacy'] ?? null]));

            if (!$demo) {
                $footerSlots[] = 'footerPages';
                $footerSlots[] = 'footerLegal';

                $modules['footerLegal'] = $this->insertModule($result, [
                    'name' => $texts['footerLegal'],
                    'type' => 'customnav',
                    'headline' => serialize(['unit' => 'h3', 'value' => $texts['legal']]),
                    'pages' => $legalPages ? serialize(array_map('strval', $legalPages)) : null,
                ]);
            }

            $modules['copyright'] = $this->insertModule($result, [
                'name' => $texts['copyright'],
                'type' => 'html',
                'html' => '<p>© {{date::Y}} {{page::rootPageTitle}}. '.$texts['rights'].'</p>',
            ]);

            if (isset($pageIds['sitemap'])) {
                $modules['bottomLinks'] = $this->insertModule($result, [
                    'name' => $texts['bottomLinks'],
                    'type' => 'customnav',
                    'pages' => serialize([(string) $pageIds['sitemap']]),
                ]);
            }

            if (isset($result->articles['sitemap'])) {
                $sitemap = $this->insertModule($result, [
                    'name' => $texts['sitemap'],
                    'type' => 'sitemap',
                    'rootPage' => $rootIds[0],
                    'defineRoot' => 1,
                ]);

                $this->insertContent($result->articles['sitemap'], [
                    'type' => 'headline',
                    'headline' => serialize(['unit' => 'h1', 'value' => $texts['sitemap']]),
                ], 0);

                $this->insertContent($result->articles['sitemap'], ['type' => 'module', 'module' => $sitemap], 1);
            }

            if (isset($result->articles['search'])) {
                $search = $this->insertModule($result, [
                    'name' => $texts['search'],
                    'type' => 'search',
                    'searchType' => 'simple',
                    'perPage' => 10,
                    'contextLength' => serialize(['48', '280']),
                    'minKeywordLength' => 3,
                ]);

                $this->insertContent($result->articles['search'], [
                    'type' => 'headline',
                    'headline' => serialize(['unit' => 'h1', 'value' => $texts['search']]),
                ], 0);

                $this->insertContent($result->articles['search'], ['type' => 'module', 'module' => $search], 1);
            }

            // Preview of the color schemes on the demo website
            if ($demo) {
                $modules['styleSwitcher'] = $this->insertModule($result, [
                    'name' => $texts['styleSwitcher'],
                    'type' => 'creuva_style_switcher',
                ]);
            }

            $result->moduleIds = $modules;

            // Demo modules (news, sitemap, …) and content
            if ($demo) {
                $result->modules += $this->demoImporter->importModules($demo, $result->themeId);
                $this->demoImporter->importContent($demo);
                $result->warnings = $this->demoImporter->getWarnings();
            }

            // Page layout with the modules assigned to the slots of page/layout/creuva
            $slots = [
                ['styleSwitcher', 'top'],
                ['header', 'header'],
                [0, 'main'],
                ...array_map(static fn (string $key): array => [$key, 'footer'], $footerSlots),
                ['copyright', 'bottom'],
                ['bottomLinks', 'bottom'],
            ];

            $layoutModules = [];

            foreach ($slots as [$key, $slot]) {
                $id = 0 === $key ? 0 : ($modules[$key] ?? null);

                if (null !== $id) {
                    $layoutModules[] = ['mod' => (string) $id, 'col' => $slot, 'enable' => '1'];
                }
            }

            // Demo modules in the layout slots, e.g. "layout: {prefooter: [partner]}"
            foreach ($demo['layout'] ?? [] as $slot => $keys) {
                foreach ((array) $keys as $key) {
                    if (null !== ($id = $this->demoImporter->resolveReference('module:'.$key))) {
                        $layoutModules[] = ['mod' => (string) $id, 'col' => (string) $slot, 'enable' => '1'];
                    }
                }
            }

            $result->layoutId = $this->insert('tl_layout', [
                'pid' => $result->themeId,
                'name' => $texts['layout'],
                'type' => 'modern',
                'template' => 'page/layout/creuva',
                'modules' => serialize($layoutModules),
                'creuvaVariant' => $options->variant,
                'creuvaTypography' => $options->typography,
                'creuvaScheme' => $options->scheme,
                'creuvaLogoHeight' => preg_match(DesignSettings::LOGO_HEIGHT_PATTERN, (string) ($demo['design']['logoHeight'] ?? '')) ? (string) $demo['design']['logoHeight'] : '',
                'creuvaStylesheets' => $this->getDemoStylesheets($demo),
            ]);

            // Including the warnings of the stylesheets
            if ($demo) {
                $result->warnings = $this->demoImporter->getWarnings();
            }

            // Styleguide of the theme as part of the demo ("styleguide: true"), it can be
            // linked in the footer with the alias "styleguide"
            if (!empty($demo['styleguide'])) {
                $styleguide = $this->styleguideInstaller->install($rootIds[0], $result->themeId, $options->language);
                $result->pages[StyleguideInstaller::ALIAS] = $styleguide['pageId'];
                $result->warnings = array_values(array_unique([...$result->warnings, ...$styleguide['warnings']]));

                foreach ($footerColumns as $key => $aliases) {
                    if (\in_array(StyleguideInstaller::ALIAS, $aliases, true)) {
                        $pages = array_values(array_filter(array_map(fn (string $alias) => $result->pages[$alias] ?? null, $aliases)));
                        $this->connection->update('tl_module', ['pages' => serialize(array_map('strval', $pages))], ['id' => $modules[$key]]);
                    }
                }
            }

            // Assign the layout to the root pages
            foreach ($rootIds as $rootId) {
                $data = ['includeLayout' => 1, 'layout' => $result->layoutId, 'tstamp' => time()];

                // Remove the design settings of an existing website root, so the ones of the new
                // layout apply (the root of a demo keeps the design of its definition)
                if (null === $demo) {
                    $data += ['creuvaScheme' => '', 'creuvaVariant' => '', 'creuvaTypography' => '', 'creuvaRadius' => ''];
                }

                $this->connection->update('tl_page', $data, ['id' => $rootId]);
                $result->rootPages[] = $rootId;
            }

            return $result;
        });
    }

    /**
     * Own stylesheets of a demo ("design: stylesheets: ['files/design/custom.css']").
     *
     * @param array<string, mixed>|null $demo
     */
    private function getDemoStylesheets(array|null $demo): string|null
    {
        $uuids = [];

        foreach ((array) ($demo['design']['stylesheets'] ?? []) as $path) {
            if (null !== ($uuid = $this->demoImporter->provideStylesheet((string) $path))) {
                $uuids[] = $uuid;
            }
        }

        return $uuids ? serialize($uuids) : null;
    }

    /**
     * Logo of the header or brand module of a demo ("logo: file:…") and its
     * image size ("logoSize: image_size:<key>" or [width, height, mode]; the
     * image size "logo" is used by default).
     *
     * @param array<string, mixed> $config
     *
     * @return array<string, mixed>
     */
    private function getDemoLogo(array $config): array
    {
        $data = [];

        if (!empty($config['logo']) && null !== ($uuid = $this->demoImporter->resolveReference($config['logo']))) {
            $data['creuvaLogo'] = $uuid;
        }

        if (isset($config['logoSize']) && null !== ($size = $this->demoImporter->resolveSize($config['logoSize']))) {
            $data['imgSize'] = $size;
        }

        return $data;
    }

    private function createRootPage(InstallOptions $options): int
    {
        $sorting = (int) $this->connection->fetchOne('SELECT MAX(sorting) FROM tl_page WHERE pid = 0') + 128;
        $hasFallback = false !== $this->connection->fetchOne("SELECT id FROM tl_page WHERE type = 'root' AND fallback = 1 AND dns = ?", [$options->dns]);

        $rootId = $this->insert('tl_page', [
            'pid' => 0,
            'sorting' => $sorting,
            'title' => $options->siteTitle,
            'alias' => $this->uniqueAlias('tl_page', $this->slug($options->siteTitle)),
            'type' => 'root',
            'dns' => $options->dns,
            'language' => $options->language,
            'fallback' => $hasFallback ? 0 : 1,
            'published' => 1,
        ]);

        return $rootId;
    }

    /**
     * @param array<string, string> $texts
     */
    private function createPages(InstallOptions $options, array $texts, InstallResult $result): int
    {
        $rootId = $this->createRootPage($options);

        $aliases = 'de' === $this->textLanguage($options->language)
            ? ['home' => 'index', 'contact' => 'kontakt', 'imprint' => 'impressum', 'privacy' => 'datenschutz', 'sitemap' => 'sitemap', 'search' => 'suche']
            : ['home' => 'index', 'contact' => 'contact', 'imprint' => 'imprint', 'privacy' => 'privacy', 'sitemap' => 'sitemap', 'search' => 'search'];

        // Only the start page and the contact page appear in the main navigation,
        // the legal pages are linked in the footer and listed in the sitemap, the
        // search results page is opened by the search in the header
        $hidden = ['imprint', 'privacy', 'sitemap', 'search'];

        $sorting = 0;

        foreach ($aliases as $key => $alias) {
            $page = [
                'pid' => $rootId,
                'sorting' => $sorting += 128,
                'title' => $texts[$key],
                'alias' => $alias,
                'type' => 'regular',
                'hide' => \in_array($key, $hidden, true) ? 1 : 0,
                'sitemap' => \in_array($key, ['imprint', 'privacy'], true) ? 'map_always' : 'map_default',
                'published' => 1,
            ];

            if ('search' === $key) {
                $page = ['searchIndexer' => 'never_index', 'robots' => 'noindex,follow', 'sitemap' => 'map_never'] + $page;
            }

            $pageId = $this->insert('tl_page', $page);

            $result->pages[$key] = $pageId;

            $articleId = $this->insert('tl_article', [
                'pid' => $pageId,
                'sorting' => 128,
                'title' => $texts[$key],
                'alias' => $this->uniqueAlias('tl_article', $alias),
                'inColumn' => 'main',
                'author' => $this->demoImporter->getAuthor(),
                'published' => 1,
            ]);

            $result->articles[$key] = $articleId;
        }

        foreach ($result->articles as $key => $articleId) {
            foreach ($this->getContentElements($key, $texts, $result->pages) as $i => $element) {
                $this->insertContent($articleId, $element, $i);
            }
        }

        return $rootId;
    }

    /**
     * @param array<string, string> $texts
     * @param array<string, int>    $pageIds
     *
     * @return list<array<string, mixed>>
     */
    private function getContentElements(string $page, array $texts, array $pageIds): array
    {
        $headline = static fn (string $text, string $unit = 'h1'): string => serialize(['unit' => $unit, 'value' => $text]);

        return match ($page) {
            'home' => [[
                'type' => 'creuva_hero',
                'creuvaKicker' => $texts['heroKicker'],
                'headline' => $headline($texts['heroHeadline']),
                'creuvaHeadlineAccent' => 'Creuva.',
                'text' => $texts['heroText'],
                'creuvaHeroMask' => 'organic',
                'creuvaUrl1' => isset($pageIds['contact']) ? '{{link_url::'.$pageIds['contact'].'}}' : '',
                'creuvaLinkTitle1' => isset($pageIds['contact']) ? $texts['cta'] : '',
            ]],
            'contact' => [[
                'type' => 'text',
                'headline' => $headline($texts['contact']),
                'text' => $texts['contactText'],
            ]],
            'sitemap', 'search' => [], // added after the sitemap and search module have been created
            default => [[
                'type' => 'text',
                'headline' => $headline($texts[$page]),
                'text' => $texts['placeholder'],
            ]],
        };
    }

    /**
     * Finds contact, legal and sitemap pages below a root page by their alias.
     *
     * @return array<string, int>
     */
    private function findPages(int $rootId): array
    {
        $found = [];
        $pids = [$rootId];

        // Walk the page tree breadth first (max. 3 levels)
        for ($level = 0; $level < 3 && $pids; ++$level) {
            $rows = $this->connection->fetchAllAssociative(
                'SELECT id, alias FROM tl_page WHERE pid IN (?) ORDER BY sorting',
                [$pids],
                [ArrayParameterType::INTEGER],
            );

            $pids = [];

            foreach ($rows as $row) {
                $pids[] = (int) $row['id'];

                foreach (self::ALIASES as $key => $aliases) {
                    if (!isset($found[$key]) && \in_array(basename((string) $row['alias']), $aliases, true)) {
                        $found[$key] = (int) $row['id'];
                    }
                }
            }
        }

        return $found;
    }

    /**
     * @param array<string, mixed> $data
     */
    private function insertModule(InstallResult $result, array $data): int
    {
        $id = $this->insert('tl_module', $data + ['pid' => $result->themeId]);
        $result->modules[$id] = $data['name'];

        return $id;
    }

    /**
     * @param array<string, mixed> $data
     */
    private function insertContent(int $articleId, array $data, int $position): void
    {
        $this->insert('tl_content', $data + [
            'pid' => $articleId,
            'ptable' => 'tl_article',
            'sorting' => ($position + 1) * 128,
        ]);
    }

    /**
     * @param array<string, mixed> $data
     */
    private function insert(string $table, array $data): int
    {
        $this->connection->insert($table, array_filter($data, static fn ($v) => null !== $v) + ['tstamp' => time()]);

        return (int) $this->connection->lastInsertId();
    }

    private function uniqueAlias(string $table, string $alias): string
    {
        $candidate = $alias;

        for ($i = 2; false !== $this->connection->fetchOne("SELECT id FROM $table WHERE alias = ?", [$candidate]); ++$i) {
            $candidate = $alias.'-'.$i;
        }

        return $candidate;
    }

    private function slug(string $text): string
    {
        $slug = strtolower(trim((string) preg_replace('/[^A-Za-z0-9]+/', '-', iconv('UTF-8', 'ASCII//TRANSLIT', $text) ?: $text), '-'));

        return '' !== $slug ? $slug : 'creuva';
    }

    private function textLanguage(string $language): string
    {
        return str_starts_with($language, 'de') ? 'de' : 'en';
    }
}
