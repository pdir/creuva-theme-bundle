<?php

declare(strict_types=1);

/*
 * Creuva theme bundle for Contao Open Source CMS
 *
 * Copyright (C) pdir GmbH <develop@pdir.de>
 */

namespace Pdir\CreuvaThemeBundle\Exporter;

use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Types\BooleanType;
use Pdir\CreuvaThemeBundle\Design\DesignSettings;
use Pdir\CreuvaThemeBundle\Design\FaviconResolver;
use Pdir\CreuvaThemeBundle\Installer\StyleguideInstaller;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Yaml\Yaml;

/**
 * Exports a website (page tree, articles, content elements, modules, forms,
 * news, events, image sizes and files) as a demo template in the format of the
 * DemoImporter: <target>/demo.yaml and the referenced files in <target>/files.
 *
 * IDs are replaced by references (page:<alias>, module:<key>, file:<path>,
 * image_size:<key> …),
 * so the demo can be imported into any installation with creuva:install --demo.
 */
class DemoExporter
{
    /**
     * Settings of the website root that are part of the demo.
     */
    private const ROOT_FIELDS = ['dateFormat', 'datimFormat', 'timeFormat', 'favicon'];

    /**
     * cssStyleSelector: the selection is derived from the CSS classes on import
     * (styleManager: column left over from the Style Manager).
     */
    private const SYSTEM_FIELDS = ['id', 'pid', 'tstamp', 'sorting', 'ptable', 'cssStyleSelector', 'styleManager'];

    /**
     * Fields that belong to the installation or are set by the importer.
     */
    private const SKIP_FIELDS = [
        'tl_page' => ['layout', 'includeLayout', 'subpageLayout', 'mobileLayout', 'dns', 'language', 'fallback', 'ac_set', 'creuvaScheme', 'creuvaVariant', 'creuvaRadius'],
        'tl_article' => ['alias', 'author'],
        'tl_form' => ['recipient', 'sendViaEmail'],
        'tl_news' => ['author', 'date', 'time'],
        'tl_calendar_events' => ['author', 'startDate', 'endDate', 'startTime', 'endTime', 'addTime'],
    ];

    /**
     * Fields that always remain in the definition (if not empty).
     */
    private const KEEP_FIELDS = ['type', 'title', 'alias', 'name'];

    /**
     * Fields with IDs of other records.
     */
    private const REFERENCES = [
        'page' => ['jumpTo', 'overviewPage', 'rootPage', 'pages', 'orderPages', 'reg_jumpTo', 'lost_jumpTo'],
        'module' => ['module', 'creuvaNavigation'],
        'form' => ['form'],
        'news_archive' => ['news_archives'],
        'calendar' => ['cal_calendar'],
    ];

    /**
     * Fields with an image size ([width, height, mode or image size ID]).
     */
    private const SIZE_FIELDS = [
        'tl_content' => 'size',
        'tl_module' => 'imgSize',
        'tl_news' => 'size',
        'tl_calendar_events' => 'size',
    ];

    private const REFERENCE_TABLES = [
        'module' => 'tl_module',
        'form' => 'tl_form',
        'news_archive' => 'tl_news_archive',
        'calendar' => 'tl_calendar',
    ];

    /**
     * Content elements that refer to other content and cannot be exported.
     */
    private const SKIP_ELEMENTS = ['alias', 'article'];

    /**
     * @var array<int, string> page ID => alias
     */
    private array $pages = [];

    /**
     * @var array<string, array<int, string>> type => ID => key
     */
    private array $keys = [];

    /**
     * @var array<string, array<int, true>> type => ID => exported
     */
    private array $exported = [];

    /**
     * @var array<int, string> image size ID => key
     */
    private array $imageSizes = [];

    /**
     * @var array<string, array{path: string, meta: string|null}> UUID (hex) => file
     */
    private array $files = [];

    /**
     * @var array<string, array<string, array{default: mixed, boolean: bool}>> table => column => info
     */
    private array $columns = [];

    /**
     * @var array<string, int>
     */
    private array $counts = [];

    /**
     * @var list<string>
     */
    private array $warnings = [];

    private bool $hasStyleguide = false;

    public function __construct(
        private readonly Connection $connection,
        private readonly DesignSettings $designSettings,
        #[Autowire(param: 'kernel.project_dir')]
        private readonly string $projectDir,
        private readonly Filesystem $filesystem = new Filesystem(),
    ) {
    }

    /**
     * @return array<string, int> counts of the exported records
     */
    public function export(int $rootId, string $targetDir, string $title): array
    {
        $this->pages = $this->keys = $this->exported = $this->imageSizes = $this->files = $this->counts = $this->warnings = [];
        $this->hasStyleguide = false;

        $root = $this->connection->fetchAssociative("SELECT * FROM tl_page WHERE id = ? AND type IN ('root', 'rootfallback')", [$rootId]);

        if (false === $root) {
            throw new \InvalidArgumentException(\sprintf('The page ID %d is not a website root.', $rootId));
        }

        $this->collectPages($rootId);

        $definition = ['title' => $title, 'root' => $this->exportRoot($root)];
        $definition += $this->exportLayout($root);

        $pages = $this->exportPages($rootId, true);

        // The styleguide is not exported, the import creates it from styleguide/styleguide.yaml
        if ($this->hasStyleguide) {
            $definition['styleguide'] = true;
        }

        // Modules, forms, news and events referenced by the pages (and by each other)
        $sections = ['forms' => [], 'news' => [], 'events' => [], 'modules' => []];

        while ($pending = $this->getPending()) {
            [$type, $id] = $pending;
            $this->exported[$type][$id] = true;
            $key = $this->keys[$type][$id];

            match ($type) {
                'module' => $sections['modules'][$key] = $this->exportModule($id),
                'form' => $sections['forms'][$key] = $this->exportForm($id),
                'news_archive' => $sections['news'][$key] = $this->exportNewsArchive($id),
                'calendar' => $sections['events'][$key] = $this->exportCalendar($id),
            };
        }

        $definition += array_filter(['imageSizes' => $this->exportImageSizes()] + $sections);
        $definition['pages'] = $pages;

        $definition = $this->exportFiles($definition, $targetDir, (string) $root['language']);

        $this->filesystem->dumpFile($targetDir.'/demo.yaml', $this->dump($definition, $root));

        return $this->counts;
    }

    /**
     * @return list<string>
     */
    public function getWarnings(): array
    {
        return array_values(array_unique($this->warnings));
    }

    /**
     * Maps the IDs of all pages below the root to their aliases.
     */
    private function collectPages(int $pid): void
    {
        $rows = $this->connection->fetchAllAssociative('SELECT id, title, alias FROM tl_page WHERE pid = ? ORDER BY sorting', [$pid]);

        foreach ($rows as $row) {
            $alias = (string) $row['alias'];

            if ('' === $alias || \in_array($alias, $this->pages, true)) {
                $alias = $this->uniqueKey($this->slug($alias ?: (string) $row['title']) ?: 'page-'.$row['id'], $this->pages);
                $this->warnings[] = \sprintf('The page "%s" (ID %d) has no unique alias, "%s" has been used.', $row['title'], $row['id'], $alias);
            }

            $this->pages[(int) $row['id']] = $alias;
            $this->collectPages((int) $row['id']);
        }
    }

    /**
     * @param array<string, mixed> $root
     *
     * @return array<string, mixed>
     */
    private function exportRoot(array $root): array
    {
        $data = array_filter(
            array_intersect_key($root, array_flip(self::ROOT_FIELDS)),
            static fn ($value): bool => null !== $value && '' !== $value,
        );

        // Favicon of the website (served as /favicon.ico)
        if (isset($data['favicon'])) {
            $file = $this->isUuid((string) $data['favicon']) ? $this->registerFile((string) $data['favicon']) : null;

            if (\is_string($file)) {
                // Including the SVG version next to it (see FaviconResolver)
                $svg = $this->connection->fetchOne(
                    "SELECT uuid FROM tl_files WHERE type = 'file' AND path = ?",
                    [FaviconResolver::getSvgPath($this->files[bin2hex((string) $data['favicon'])]['path'])],
                );

                if (\is_string($svg)) {
                    $this->registerFile($svg);
                }

                $data['favicon'] = $file;
            } else {
                unset($data['favicon']);
            }
        }

        // Effective design of the website (page layout and root settings)
        $layout = $root['includeLayout'] ? $this->connection->fetchAssociative('SELECT * FROM tl_layout WHERE id = ?', [$root['layout']]) : false;
        $design = $this->designSettings->resolve($layout ?: null, $root);

        if ($design['primary'] || $design['accent']) {
            $this->warnings[] = 'The custom colors of the page layout are not part of the demo, please choose a color scheme.';
        }

        return $data + array_filter([
            'creuvaScheme' => $design['scheme'],
            'creuvaVariant' => $design['variant'],
            'creuvaRadius' => $design['radius'],
        ]);
    }

    /**
     * Header button, brand and footer columns of the Creuva modules in the page
     * layout (the installer creates these modules from the definition).
     *
     * @param array<string, mixed> $root
     *
     * @return array<string, mixed>
     */
    private function exportLayout(array $root): array
    {
        $layout = $root['includeLayout'] ? $this->connection->fetchAssociative('SELECT * FROM tl_layout WHERE id = ?', [$root['layout']]) : false;

        if (false === $layout) {
            $this->warnings[] = 'The website root has no page layout, header and footer have not been exported.';

            return [];
        }

        $data = [];

        // All image sizes of the theme, also the ones that are not used yet
        foreach ($this->connection->fetchFirstColumn('SELECT id FROM tl_image_size WHERE pid = ? ORDER BY id', [(int) $layout['pid']]) as $id) {
            $this->registerImageSize((int) $id);
        }

        if (preg_match(DesignSettings::LOGO_HEIGHT_PATTERN, (string) ($layout['creuvaLogoHeight'] ?? ''))) {
            $data['design']['logoHeight'] = (string) $layout['creuvaLogoHeight'];
        }

        foreach ($this->unserialize($layout['modules']) ?? [] as $slot) {
            $module = $this->connection->fetchAssociative('SELECT * FROM tl_module WHERE id = ?', [(int) ($slot['mod'] ?? 0)]);

            if (false === $module) {
                continue;
            }

            if ('creuva_header' === $module['type'] && preg_match('/^\{\{link_url::([^}|]+)\}\}$/', (string) $module['creuvaUrl1'], $matches)) {
                $alias = ctype_digit($matches[1]) ? ($this->pages[(int) $matches[1]] ?? null) : $matches[1];

                if (null !== $alias && '' !== (string) $module['creuvaLinkTitle1']) {
                    $data['header']['cta'] = ['page' => $alias, 'title' => (string) $module['creuvaLinkTitle1']];
                }
            }

            if ('creuva_header' === $module['type'] && $module['creuvaSearch'] && null !== ($alias = $this->pages[(int) $module['creuvaSearchPage']] ?? null)) {
                $data['header']['search'] = $alias;
            }

            if ('creuva_header' === $module['type']) {
                $data['header'] = ($data['header'] ?? []) + $this->exportLogo($module);
            }

            if ('creuva_brand' === $module['type']) {
                $data['brand'] = array_filter([
                    'claim' => (string) $module['creuvaClaim'],
                    'social' => array_values(array_filter(array_map(trim(...), explode("\n", (string) $module['creuvaSocialLinks'])))),
                ]) + $this->exportLogo($module);
            }

            if ('footer' === ($slot['col'] ?? null) && 'customnav' === $module['type']) {
                $headline = $this->unserialize($module['headline']);
                $pages = array_map(fn ($id) => $this->pages[(int) $id] ?? null, $this->unserialize($module['pages']) ?? []);

                $data['footer'][] = [
                    'headline' => (string) ($headline['value'] ?? $module['name']),
                    'pages' => array_values(array_filter($pages)),
                ];
            }
        }

        return array_filter($data);
    }

    /**
     * Logo of the header or brand module ("logo: file:…") and its image size
     * ("logoSize: image_size:<key>" or [width, height, mode]).
     *
     * @param array<string, mixed> $module
     *
     * @return array<string, mixed>
     */
    private function exportLogo(array $module): array
    {
        $logo = (string) ($module['creuvaLogo'] ?? '');

        return array_filter([
            'logo' => $this->isUuid($logo) && \is_string($file = $this->registerFile($logo)) ? $file : null,
            'logoSize' => $this->convertSize('tl_module', 'imgSize', $module['imgSize'] ?? null, $module),
        ]);
    }

    /**
     * Image sizes with their media queries ("items").
     *
     * @return array<string, array<string, mixed>>
     */
    private function exportImageSizes(): array
    {
        $sizes = [];

        foreach ($this->imageSizes as $id => $key) {
            $size = $this->cleanRow('tl_image_size', $this->fetchRow('tl_image_size', $id));

            foreach ($this->connection->fetchAllAssociative('SELECT * FROM tl_image_size_item WHERE pid = ? ORDER BY sorting', [$id]) as $row) {
                $size['items'][] = $this->cleanRow('tl_image_size_item', $row);
            }

            $sizes[$key] = $size;
            $this->count('imageSizes');
        }

        return $sizes;
    }

    /**
     * Converts an image size: IDs of tl_image_size become "image_size:<key>",
     * custom sizes remain [width, height, mode].
     *
     * @param array<string, mixed> $row
     *
     * @return string|list<string>|null
     */
    private function convertSize(string $table, string $field, mixed $value, array $row): string|array|null
    {
        $size = $this->unserialize($value);

        if (null === $size || !array_filter($size, static fn ($v): bool => '' !== (string) $v)) {
            return null;
        }

        $mode = (string) ($size[2] ?? '');

        if (ctype_digit($mode)) {
            if (null === ($key = $this->registerImageSize((int) $mode))) {
                $this->warnings[] = \sprintf('%s.%s of record ID %d refers to the missing image size ID %s and has been removed.', $table, $field, $row['id'], $mode);

                return null;
            }

            return 'image_size:'.$key;
        }

        // Image sizes of the configuration (contao.image.sizes) are not part of the demo
        if (str_starts_with($mode, '_')) {
            $this->warnings[] = \sprintf('The image size "%s" is defined in the configuration (contao.image.sizes) and must exist in the target installation.', substr($mode, 1));
        }

        return array_map(strval(...), array_values($size));
    }

    private function registerImageSize(int $id): string|null
    {
        if (isset($this->imageSizes[$id])) {
            return $this->imageSizes[$id];
        }

        $name = $this->connection->fetchOne('SELECT name FROM tl_image_size WHERE id = ?', [$id]);

        if (false === $name) {
            return null;
        }

        return $this->imageSizes[$id] = $this->uniqueKey($this->slug((string) $name) ?: 'size-'.$id, $this->imageSizes);
    }

    /**
     * @return list<array<string, mixed>>
     */
    private function exportPages(int $pid, bool $isRoot = false): array
    {
        $pages = [];

        foreach ($this->connection->fetchAllAssociative('SELECT * FROM tl_page WHERE pid = ? ORDER BY sorting', [$pid]) as $row) {
            $id = (int) $row['id'];

            if ($isRoot && StyleguideInstaller::ALIAS === $row['alias']) {
                $this->hasStyleguide = true;
                continue;
            }
            $page = $this->cleanRow('tl_page', $row);
            $page['alias'] = $this->pages[$id];

            if ($articles = $this->exportArticles($id)) {
                $page['articles'] = $articles;
            }

            if ($children = $this->exportPages($id)) {
                $page['children'] = $children;
            }

            $pages[] = $page;
            $this->count('pages');
        }

        return $pages;
    }

    /**
     * @return list<array<string, mixed>>
     */
    private function exportArticles(int $pageId): array
    {
        $articles = [];

        foreach ($this->connection->fetchAllAssociative('SELECT * FROM tl_article WHERE pid = ? ORDER BY sorting', [$pageId]) as $row) {
            $article = $this->cleanRow('tl_article', $row);

            if ($elements = $this->exportElements((int) $row['id'], 'tl_article')) {
                $article['elements'] = $elements;
            }

            $articles[] = $article;
            $this->count('articles');
        }

        return $articles;
    }

    /**
     * @return list<array<string, mixed>>
     */
    private function exportElements(int $pid, string $ptable): array
    {
        // Articles may have an empty ptable in old installations
        $query = 'tl_article' === $ptable
            ? "SELECT * FROM tl_content WHERE pid = ? AND (ptable = ? OR ptable = '') ORDER BY sorting"
            : 'SELECT * FROM tl_content WHERE pid = ? AND ptable = ? ORDER BY sorting';

        $elements = [];

        foreach ($this->connection->fetchAllAssociative($query, [$pid, $ptable]) as $row) {
            if (\in_array($row['type'], self::SKIP_ELEMENTS, true)) {
                $this->warnings[] = \sprintf('The content element ID %d of type "%s" refers to other content and has been skipped.', $row['id'], $row['type']);
                continue;
            }

            $element = $this->cleanRow('tl_content', $row);

            if ($children = $this->exportElements((int) $row['id'], 'tl_content')) {
                $element['children'] = $children;
            }

            $elements[] = $element;
            $this->count('elements');
        }

        return $elements;
    }

    /**
     * @return array<string, mixed>
     */
    private function exportModule(int $id): array
    {
        $this->count('modules');

        return $this->cleanRow('tl_module', $this->fetchRow('tl_module', $id));
    }

    /**
     * @return array<string, mixed>
     */
    private function exportForm(int $id): array
    {
        $form = $this->cleanRow('tl_form', $this->fetchRow('tl_form', $id));

        foreach ($this->connection->fetchAllAssociative('SELECT * FROM tl_form_field WHERE pid = ? ORDER BY sorting', [$id]) as $row) {
            $form['fields'][] = $this->cleanRow('tl_form_field', $row);
        }

        $this->count('forms');

        return $form;
    }

    /**
     * @return array<string, mixed>
     */
    private function exportNewsArchive(int $id): array
    {
        $archive = $this->cleanRow('tl_news_archive', $this->fetchRow('tl_news_archive', $id));

        foreach ($this->connection->fetchAllAssociative('SELECT * FROM tl_news WHERE pid = ? ORDER BY date DESC', [$id]) as $row) {
            $item = $this->cleanRow('tl_news', $row);
            $item['headline'] = (string) $row['headline'];
            $item['alias'] = (string) $row['alias'] ?: $this->slug((string) $row['headline']);
            $item['date'] = date('Y-m-d H:i', (int) $row['date']);

            if ($elements = $this->exportElements((int) $row['id'], 'tl_news')) {
                $item['elements'] = $elements;
            }

            $archive['items'][] = $item;
            $this->count('news');
        }

        return $archive;
    }

    /**
     * @return array<string, mixed>
     */
    private function exportCalendar(int $id): array
    {
        $calendar = $this->cleanRow('tl_calendar', $this->fetchRow('tl_calendar', $id));

        foreach ($this->connection->fetchAllAssociative('SELECT * FROM tl_calendar_events WHERE pid = ? ORDER BY startTime', [$id]) as $row) {
            $item = $this->cleanRow('tl_calendar_events', $row);
            $item['title'] = (string) $row['title'];
            $item['alias'] = (string) $row['alias'] ?: $this->slug((string) $row['title']);
            $item['date'] = date('Y-m-d', (int) $row['startDate']);

            if ($row['endDate'] && date('Y-m-d', (int) $row['endDate']) !== $item['date']) {
                $item['endDate'] = date('Y-m-d', (int) $row['endDate']);
            }

            if ($row['addTime']) {
                $item['startTime'] = date('H:i', (int) $row['startTime']);
                $item['endTime'] = date('H:i', (int) $row['endTime']);
            }

            if ($elements = $this->exportElements((int) $row['id'], 'tl_calendar_events')) {
                $item['elements'] = $elements;
            }

            $calendar['items'][] = $item;
            $this->count('events');
        }

        return $calendar;
    }

    /**
     * Removes system fields and default values and replaces IDs by references.
     *
     * @param array<string, mixed> $row
     *
     * @return array<string, mixed>
     */
    private function cleanRow(string $table, array $row): array
    {
        $columns = $this->getColumns($table);
        $skip = [...self::SYSTEM_FIELDS, ...self::SKIP_FIELDS[$table] ?? []];
        $data = [];

        foreach ($row as $field => $value) {
            if (\in_array($field, $skip, true) || null === $value || '' === $value) {
                continue;
            }

            $keep = \in_array($field, self::KEEP_FIELDS, true);

            if (!$keep && isset($columns[strtolower($field)]) && (string) $value === (string) $columns[strtolower($field)]['default']) {
                continue;
            }

            // Boolean columns (e.g. tl_image_size.lazyLoading) as true/false
            $value = ($columns[strtolower($field)]['boolean'] ?? false) ? (bool) $value : $this->convertValue($table, $field, $value, $row);

            if (null !== $value && '' !== $value && [] !== $value) {
                $data[$field] = $value;
            }
        }

        // The importer publishes records and adds articles to the main column by default
        if (\in_array($table, ['tl_page', 'tl_article', 'tl_news', 'tl_calendar_events'], true) && isset($columns['published'])) {
            unset($data['published']);

            if (!$row['published']) {
                $data['published'] = false;
            }
        }

        if ('tl_article' === $table && 'main' === ($data['inColumn'] ?? null)) {
            unset($data['inColumn']);
        }

        // Type, title, alias and name first
        return array_replace(array_intersect_key(array_flip(self::KEEP_FIELDS), $data), $data);
    }

    /**
     * @param array<string, mixed> $row
     */
    private function convertValue(string $table, string $field, mixed $value, array $row): mixed
    {
        // Headlines and CSS IDs in the short form of the importer
        if (\in_array($field, ['headline', 'sectionHeadline'], true) && \is_array($headline = $this->unserialize($value))) {
            if ('' === (string) ($headline['value'] ?? '')) {
                return null;
            }

            return 'h2' === ($headline['unit'] ?? 'h2') ? (string) $headline['value'] : ['unit' => $headline['unit'], 'value' => (string) $headline['value']];
        }

        if ('cssID' === $field && \is_array($cssId = $this->unserialize($value))) {
            [$id, $class] = [(string) ($cssId[0] ?? ''), (string) ($cssId[1] ?? '')];

            return '' === $id ? $class : [$id, $class];
        }

        if ($field === (self::SIZE_FIELDS[$table] ?? null)) {
            return $this->convertSize($table, $field, $value, $row);
        }

        foreach (self::REFERENCES as $type => $fields) {
            if (\in_array($field, $fields, true)) {
                return $this->convertReference($type, $table, $field, $value, $row);
            }
        }

        if (\is_string($value) && $this->isUuid($value) && null !== ($file = $this->registerFile($value))) {
            if (\is_array($file)) {
                $this->warnings[] = \sprintf('%s.%s of record ID %d refers to a folder and has been removed.', $table, $field, $row['id']);

                return null;
            }

            return $file;
        }

        $array = $this->unserialize($value);

        if (\is_array($array)) {
            $files = [];

            foreach ($array as $item) {
                if (!\is_string($item) || !$this->isUuid($item) || null === ($file = $this->registerFile($item))) {
                    $files = null;
                    break;
                }

                $files[] = $file;
            }

            // Folders are expanded to their files
            return null !== $files ? array_merge(...array_map(static fn ($f) => (array) $f, $files)) : $this->convertInsertTags($array);
        }

        return \is_string($value) ? $this->convertInsertTags($value) : $value;
    }

    /**
     * @param array<string, mixed> $row
     *
     * @return string|list<string>|null
     */
    private function convertReference(string $type, string $table, string $field, mixed $value, array $row): string|array|null
    {
        $ids = $this->unserialize($value);
        $multiple = \is_array($ids);
        $references = [];

        foreach ($multiple ? $ids : [$value] as $id) {
            $id = (int) $id;

            if ($id < 1) {
                continue;
            }

            if ('page' === $type) {
                if (!isset($this->pages[$id])) {
                    $this->warnings[] = \sprintf('%s.%s of "%s" refers to the page ID %d outside of the website and has been removed.', $table, $field, $row['title'] ?? $row['name'] ?? $row['id'], $id);
                    continue;
                }

                $references[] = 'page:'.$this->pages[$id];
                continue;
            }

            $key = $this->registerRecord($type, $id);

            if (null === $key) {
                $this->warnings[] = \sprintf('%s.%s refers to the missing %s ID %d and has been removed.', $table, $field, $type, $id);
                continue;
            }

            $references[] = $type.':'.$key;
        }

        if ($multiple) {
            return $references;
        }

        return $references[0] ?? null;
    }

    /**
     * Assigns a key to a module, form, news archive or calendar and marks it
     * for the export.
     */
    private function registerRecord(string $type, int $id): string|null
    {
        if (isset($this->keys[$type][$id])) {
            return $this->keys[$type][$id];
        }

        $table = self::REFERENCE_TABLES[$type];

        if (!$this->connection->createSchemaManager()->tablesExist([$table])) {
            return null;
        }

        $row = $this->connection->fetchAssociative("SELECT * FROM $table WHERE id = ?", [$id]);

        if (false === $row) {
            return null;
        }

        $name = (string) ($row['alias'] ?? '') ?: (string) ($row['name'] ?? '') ?: (string) ($row['title'] ?? '');

        return $this->keys[$type][$id] = $this->uniqueKey($this->slug($name) ?: $type.'-'.$id, $this->keys[$type] ?? []);
    }

    /**
     * @return array{0: string, 1: int}|null
     */
    private function getPending(): array|null
    {
        foreach ($this->keys as $type => $keys) {
            foreach (array_keys($keys) as $id) {
                if (!isset($this->exported[$type][$id])) {
                    return [$type, $id];
                }
            }
        }

        return null;
    }

    /**
     * Replaces page IDs in insert tags by page aliases.
     */
    private function convertInsertTags(mixed $value): mixed
    {
        if (\is_array($value)) {
            return array_map($this->convertInsertTags(...), $value);
        }

        if (!\is_string($value) || !str_contains($value, '{{')) {
            return $value;
        }

        $value = (string) preg_replace_callback(
            '/\{\{(link|link_url|link_open|link_title|link_name)::(\d+)((?:\|[^}]*)?)\}\}/',
            function (array $matches): string {
                if (!isset($this->pages[(int) $matches[2]])) {
                    $this->warnings[] = \sprintf('The insert tag "%s" refers to a page outside of the website.', $matches[0]);

                    return $matches[0];
                }

                return '{{'.$matches[1].'::'.$this->pages[(int) $matches[2]].$matches[3].'}}';
            },
            $value,
        );

        if (preg_match_all('/\{\{(insert_module|insert_article|insert_content|file|picture|image|figure|news|news_url|event|event_url)::[^}]+\}\}/', $value, $matches)) {
            foreach ($matches[0] as $tag) {
                $this->warnings[] = \sprintf('The insert tag "%s" refers to an ID and has not been converted.', $tag);
            }
        }

        return $value;
    }

    /**
     * @return string|list<string>|null file reference(s), a folder is expanded to its files
     */
    private function registerFile(string $uuid): string|array|null
    {
        $file = $this->connection->fetchAssociative('SELECT type, path, meta FROM tl_files WHERE uuid = ?', [$uuid]);

        if (false === $file) {
            return null;
        }

        if ('folder' === $file['type']) {
            $children = $this->connection->fetchAllAssociative(
                "SELECT uuid FROM tl_files WHERE type = 'file' AND path LIKE ? ORDER BY path",
                [addcslashes((string) $file['path'], '%_').'/%'],
            );

            return array_values(array_filter(array_map(fn (array $child) => $this->registerFile((string) $child['uuid']), $children)));
        }

        $key = bin2hex($uuid);
        $this->files[$key] = ['path' => (string) $file['path'], 'meta' => $file['meta']];

        return 'file:@'.$key.'@';
    }

    /**
     * Copies the files to <target>/files, replaces the placeholders by paths
     * relative to the common folder of all files and adds the file metadata.
     *
     * @param array<string, mixed> $definition
     *
     * @return array<string, mixed>
     */
    private function exportFiles(array $definition, string $targetDir, string $language): array
    {
        $paths = array_column($this->files, 'path');
        $base = $paths ? $this->getCommonFolder($paths) : '';
        $replace = [];
        $meta = [];

        foreach ($this->files as $key => $file) {
            $relative = ltrim(substr($file['path'], \strlen($base)), '/');
            $replace['file:@'.$key.'@'] = 'file:'.$relative;

            $source = $this->projectDir.'/'.$file['path'];

            if (!is_file($source)) {
                $this->warnings[] = \sprintf('The file "%s" does not exist and has not been exported.', $file['path']);
                continue;
            }

            $this->filesystem->copy($source, $targetDir.'/files/'.$relative, true);
            $this->count('files');

            $fileMeta = $this->unserialize($file['meta']) ?? [];
            $fileMeta = $fileMeta[$language] ?? reset($fileMeta) ?: [];

            if ($fileMeta = array_filter(array_intersect_key($fileMeta, array_flip(['title', 'alt', 'link', 'caption'])))) {
                $meta[$relative] = $fileMeta;
            }
        }

        array_walk_recursive($definition, static function (&$value) use ($replace): void {
            if (\is_string($value) && isset($replace[$value])) {
                $value = $replace[$value];
            }
        });

        if ($meta) {
            ksort($meta);
            $definition = ['title' => $definition['title'], 'root' => $definition['root'], 'files' => $meta] + $definition;
        }

        return $definition;
    }

    /**
     * @param list<string> $paths
     */
    private function getCommonFolder(array $paths): string
    {
        $parts = explode('/', \dirname($paths[0]));

        foreach ($paths as $path) {
            $folder = explode('/', \dirname($path));
            $i = 0;

            while ($i < \count($parts) && $i < \count($folder) && $parts[$i] === $folder[$i]) {
                ++$i;
            }

            $parts = \array_slice($parts, 0, $i);
        }

        return implode('/', $parts);
    }

    /**
     * @param array<string, mixed> $definition
     * @param array<string, mixed> $root
     */
    private function dump(array $definition, array $root): string
    {
        $header = \sprintf(
            "# %s\n#\n# Exported from \"%s\" on %s with creuva:export.\n# Import: copy this folder to files/<name> and run\n#   vendor/bin/contao-console creuva:install --demo=files/<name>\n# See src/Installer/DemoImporter.php for the value syntax (file:, page:, module:, image_size:, …).\n\n",
            $definition['title'],
            $root['title'],
            date('Y-m-d H:i'),
        );

        $yaml = $this->toYaml($definition);

        if (Yaml::parse($yaml) !== $definition) {
            throw new \LogicException('The demo definition could not be converted to YAML without loss.');
        }

        return $header.$yaml;
    }

    /**
     * Dumps the definition like the demos of the bundle: records with scalar
     * values only are written inline ({ type: text, … }) if the line is short
     * enough, multi-line strings as literal blocks.
     *
     * @param array<mixed> $data
     */
    private function toYaml(array $data, int $level = 0): string
    {
        $indent = str_repeat('  ', $level);
        $isList = array_is_list($data);
        $yaml = '';

        foreach ($data as $key => $value) {
            $prefix = $indent.($isList ? '- ' : Yaml::dump((string) $key).': ');

            if (\is_array($value) && [] !== $value) {
                $inline = Yaml::dump($value, 0);

                if ($this->isFlat($value) && \strlen($prefix.$inline) <= 160) {
                    $yaml .= $prefix.$inline."\n";
                } elseif ($isList) {
                    $yaml .= $prefix.ltrim($this->toYaml($value, $level + 1));
                } else {
                    $yaml .= rtrim($prefix)."\n".$this->toYaml($value, $level + 1);
                }

                continue;
            }

            if (\is_string($value) && str_contains($value, "\n")) {
                // Literal block, indented below the key
                $block = Yaml::dump(['x' => $value], 1, 2, Yaml::DUMP_MULTI_LINE_LITERAL_BLOCK);
                $lines = explode("\n", rtrim(substr($block, 3), "\n"));
                $yaml .= $prefix.array_shift($lines)."\n";

                foreach ($lines as $line) {
                    $yaml .= ('' === $line ? '' : $indent.$line)."\n";
                }

                continue;
            }

            $yaml .= $prefix.Yaml::dump($value, 0, 2, Yaml::DUMP_EMPTY_ARRAY_AS_SEQUENCE)."\n";
        }

        return $yaml;
    }

    /**
     * @param array<mixed> $data
     */
    private function isFlat(array $data): bool
    {
        foreach ($data as $value) {
            if (\is_array($value) ? !$this->isFlat($value) : \is_string($value) && str_contains($value, "\n")) {
                return false;
            }
        }

        return true;
    }

    /**
     * @return array<string, mixed>
     */
    private function fetchRow(string $table, int $id): array
    {
        return $this->connection->fetchAssociative("SELECT * FROM $table WHERE id = ?", [$id]) ?: [];
    }

    /**
     * @return array<string, array{default: mixed, boolean: bool}>
     */
    private function getColumns(string $table): array
    {
        if (!isset($this->columns[$table])) {
            $this->columns[$table] = [];

            foreach ($this->connection->createSchemaManager()->listTableColumns($table) as $column) {
                $this->columns[$table][strtolower($column->getName())] = ['default' => $column->getDefault(), 'boolean' => $column->getType() instanceof BooleanType];
            }
        }

        return $this->columns[$table];
    }

    private function isUuid(string $value): bool
    {
        return 16 === \strlen($value) && (!preg_match('//u', $value) || preg_match('/[\x00-\x08\x0E-\x1F\x7F]/', $value));
    }

    /**
     * @return array<mixed>|null
     */
    private function unserialize(mixed $value): array|null
    {
        if (!\is_string($value) || !str_starts_with($value, 'a:')) {
            return null;
        }

        $array = @unserialize($value, ['allowed_classes' => false]);

        return \is_array($array) ? $array : null;
    }

    /**
     * @param array<array-key, string> $existing
     */
    private function uniqueKey(string $key, array $existing): string
    {
        $candidate = $key;

        for ($i = 2; \in_array($candidate, $existing, true); ++$i) {
            $candidate = $key.'-'.$i;
        }

        return $candidate;
    }

    private function slug(string $text): string
    {
        $text = strtr($text, ['ä' => 'ae', 'ö' => 'oe', 'ü' => 'ue', 'ß' => 'ss', 'Ä' => 'ae', 'Ö' => 'oe', 'Ü' => 'ue']);

        return strtolower(trim((string) preg_replace('/[^A-Za-z0-9]+/', '-', $text), '-'));
    }

    private function count(string $type): void
    {
        $this->counts[$type] = ($this->counts[$type] ?? 0) + 1;
    }
}
