<?php

declare(strict_types=1);

/*
 * Creuva theme bundle for Contao Open Source CMS
 *
 * Copyright (C) pdir GmbH <develop@pdir.de>
 */

namespace Pdir\CreuvaThemeBundle\Installer;

use Contao\CoreBundle\Filesystem\VirtualFilesystemInterface;
use Doctrine\DBAL\Connection;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\Finder\Finder;
use Symfony\Component\Yaml\Yaml;

/**
 * Imports a demo site (pages, articles, content elements, news, forms, files)
 * from a YAML definition in demo/<name>/demo.yaml or in a folder of the file
 * manager (files/<folder>/demo.yaml, with the demo files in files/<folder>/files).
 *
 * Values in the definition are resolved as follows:
 *
 *   file:<path>          binary UUID of a demo file (multiSRC: list of file:…)
 *   page:<alias>         ID of a demo page
 *   module:<key>         ID of a demo front end module
 *   form:<key>           ID of a demo form
 *   news_archive:<key>   ID of a demo news archive
 *   calendar:<key>       ID of a demo event calendar
 *   image_size:<key>     ID of a demo image size ("size: image_size:card" is
 *                        short for ['', '', 'image_size:card'])
 *   headline: "Text"     headline as h2 (or {value: …, unit: h1})
 *   cssID: "classes"     CSS classes (or [id, classes])
 *   any other array      serialized
 *
 * Insert tags like {{link_url::kontakt}} can reference page aliases directly.
 */
class DemoImporter
{
    public const FILES_FOLDER = 'creuva-demo';

    /**
     * @var array<string, array<string, int|string>> type => key => ID/UUID
     */
    private array $refs = [];

    /**
     * @var list<string>
     */
    private array $warnings = [];

    private string $demoDir = '';

    public function __construct(
        private readonly Connection $connection,
        #[Autowire(service: 'contao.filesystem.virtual.files')]
        private readonly VirtualFilesystemInterface $filesStorage,
        #[Autowire(param: 'kernel.project_dir')]
        private readonly string $projectDir,
        #[Autowire(param: 'contao.upload_path')]
        private readonly string $uploadPath,
    ) {
    }

    /**
     * Applies the root page settings of the demo (e.g. date formats).
     *
     * @param array<string, mixed> $definition
     */
    public function configureRootPage(array $definition, int $rootId): void
    {
        if ($data = $this->resolveRow($definition['root'] ?? [])) {
            $this->connection->update('tl_page', $data, ['id' => $rootId]);
        }
    }

    /**
     * @return array<string, string> demo name => title
     */
    public function getAvailableDemos(): array
    {
        $demos = [];

        foreach (glob($this->getDemoRoot().'/*/demo.yaml') ?: [] as $file) {
            $definition = Yaml::parseFile($file);
            $demos[basename(\dirname($file))] = (string) ($definition['title'] ?? basename(\dirname($file)));
        }

        ksort($demos);

        return $demos;
    }

    /**
     * Returns whether the demo is a folder in the file manager ("files/<folder>")
     * instead of a demo of the bundle.
     */
    public function isLocalDemo(string $demo): bool
    {
        return str_contains($demo, '/');
    }

    /**
     * @return array<string, mixed>
     */
    public function load(string $demo): array
    {
        return Yaml::parseFile($this->getDemoDir($demo).'/demo.yaml');
    }

    /**
     * Returns the directory of a demo of the bundle ("standard") or of a folder
     * in the file manager ("files/my-demo").
     *
     * @throws \InvalidArgumentException
     */
    public function getDemoDir(string $demo): string
    {
        if (!$this->isLocalDemo($demo)) {
            $dir = $this->getDemoRoot().'/'.$demo;

            if (!preg_match('/^[a-z0-9-]+$/', $demo) || !is_file($dir.'/demo.yaml')) {
                throw new \InvalidArgumentException(\sprintf('Unknown demo "%s" (available: %s, or a folder like "%s/my-demo").', $demo, implode(', ', array_keys($this->getAvailableDemos())), $this->uploadPath));
            }

            return $dir;
        }

        $uploadDir = realpath($this->projectDir.'/'.$this->uploadPath);
        $path = str_replace('\\', '/', $demo);

        // Relative to the project directory (e.g. "files/my-demo")
        if (!str_starts_with($path, '/') && !preg_match('#^[A-Za-z]:/#', $path)) {
            $path = $this->projectDir.'/'.$path;
        }

        $dir = realpath($path);

        if (false === $uploadDir || false === $dir || !str_starts_with($dir.'/', $uploadDir.'/') || $dir === $uploadDir) {
            throw new \InvalidArgumentException(\sprintf('The demo folder "%s" does not exist or is not inside the "%s" directory.', $demo, $this->uploadPath));
        }

        if (str_starts_with($dir.'/', $uploadDir.'/'.self::FILES_FOLDER.'/')) {
            throw new \InvalidArgumentException(\sprintf('The demo folder must not be inside "%s/%s" (it is deleted by creuva:reset).', $this->uploadPath, self::FILES_FOLDER));
        }

        if (!is_file($dir.'/demo.yaml')) {
            throw new \InvalidArgumentException(\sprintf('The folder "%s" does not contain a demo.yaml file.', $demo));
        }

        return $dir;
    }

    /**
     * Name of the demo, used for the folder files/creuva-demo/<name>.
     */
    public function getDemoName(string $demo): string
    {
        if (!$this->isLocalDemo($demo)) {
            return $demo;
        }

        $name = strtolower(trim((string) preg_replace('/[^A-Za-z0-9]+/', '-', basename($this->getDemoDir($demo))), '-'));

        return 'local-'.($name ?: 'demo');
    }

    /**
     * Copies the demo files to the file manager.
     *
     * @param array<string, mixed> $definition
     */
    public function prepare(string $demo, array $definition, string $language = 'de'): void
    {
        $this->refs = [];
        $this->warnings = [];
        $this->demoDir = $this->getDemoDir($demo);

        $this->importFiles($this->getFilesFolder($demo, $definition));

        // File metadata (alt texts, titles for downloads)
        foreach ($definition['files'] ?? [] as $path => $meta) {
            if (!isset($this->refs['file'][$path])) {
                continue;
            }

            $this->connection->update(
                'tl_files',
                ['meta' => serialize([$language => $meta + ['title' => '', 'alt' => '', 'link' => '', 'caption' => '']])],
                ['uuid' => $this->refs['file'][$path]],
            );
        }
    }

    /**
     * Creates the image sizes of the demo (tl_image_size with the media queries
     * of tl_image_size_item in "items") in the theme.
     *
     * @param array<string, mixed> $definition
     *
     * @return array<string, int> key => image size ID
     */
    public function importImageSizes(array $definition, int $themeId): array
    {
        foreach ($definition['imageSizes'] ?? [] as $key => $size) {
            $this->refs['image_size'][(string) $key] = $this->importImageSize($size, $themeId);
        }

        return $this->refs['image_size'] ?? [];
    }

    /**
     * Creates an image size (e.g. the default logo size of the installer).
     *
     * @param array<string, mixed> $size
     */
    public function importImageSize(array $size, int $themeId): int
    {
        $items = $size['items'] ?? [];
        unset($size['items']);

        $id = $this->insert('tl_image_size', ($this->resolveRow($size) ?? []) + ['pid' => $themeId]);

        foreach (array_values($items) as $i => $item) {
            $this->insert('tl_image_size_item', ($this->resolveRow($item) ?? []) + ['pid' => $id, 'sorting' => ($i + 1) * 128]);
        }

        return $id;
    }

    /**
     * Creates the page tree below the given root page.
     *
     * @param array<string, mixed> $definition
     *
     * @return array<string, int> alias => page ID
     */
    public function importPages(array $definition, int $rootId): array
    {
        $this->importPageTree($definition['pages'] ?? [], $rootId);

        return $this->refs['page'] ?? [];
    }

    /**
     * Creates forms, news archives and front end modules (they refer to pages).
     *
     * @param array<string, mixed> $definition
     *
     * @return array<int, string> module ID => name
     */
    public function importModules(array $definition, int $themeId): array
    {
        foreach ($definition['forms'] ?? [] as $key => $form) {
            $this->importForm((string) $key, $form);
        }

        $hasNews = $this->tableExists('tl_news');

        foreach ($definition['news'] ?? [] as $key => $archive) {
            if (!$hasNews) {
                $this->warnings[] = 'The news bundle is not installed, the demo news have been skipped.';
                break;
            }

            $this->importNewsArchive((string) $key, $archive);
        }

        $hasEvents = $this->tableExists('tl_calendar_events');

        foreach ($definition['events'] ?? [] as $key => $calendar) {
            if (!$hasEvents) {
                $this->warnings[] = 'The calendar bundle is not installed, the demo events have been skipped.';
                break;
            }

            $this->importEventCalendar((string) $key, $calendar);
        }

        $modules = [];

        foreach ($definition['modules'] ?? [] as $key => $module) {
            $data = $this->resolveRow($module);

            if (null === $data) {
                continue;
            }

            $id = $this->insert('tl_module', $data + ['pid' => $themeId]);
            $this->refs['module'][$key] = $id;
            $modules[$id] = (string) $data['name'];
        }

        return $modules;
    }

    /**
     * Creates the articles and content elements of all pages and news.
     *
     * @param array<string, mixed> $definition
     */
    public function importContent(array $definition): void
    {
        $this->importArticles($definition['pages'] ?? []);

        foreach ($definition['news'] ?? [] as $archive) {
            foreach ($archive['items'] ?? [] as $item) {
                if (!isset($item['alias'], $this->refs['news'][$item['alias']])) {
                    continue;
                }

                $this->importElements($item['elements'] ?? [], $this->refs['news'][$item['alias']], 'tl_news');
            }
        }

        foreach ($definition['events'] ?? [] as $calendar) {
            foreach ($calendar['items'] ?? [] as $item) {
                if (!isset($item['alias'], $this->refs['event'][$item['alias']])) {
                    continue;
                }

                $this->importElements($item['elements'] ?? [], $this->refs['event'][$item['alias']], 'tl_calendar_events');
            }
        }
    }

    /**
     * @return list<string>
     */
    public function getWarnings(): array
    {
        return array_values(array_unique($this->warnings));
    }

    public function resolvePage(string $alias): int|null
    {
        return $this->refs['page'][$alias] ?? null;
    }

    /**
     * Resolves a value of the definition (e.g. "file:design/logo.png"), arrays are
     * serialized. Returns null and adds a warning if a reference cannot be resolved.
     */
    public function resolveReference(mixed $value): mixed
    {
        try {
            return $this->resolveValue($value);
        } catch (\OutOfBoundsException $e) {
            $this->warnings[] = $e->getMessage();

            return null;
        }
    }

    /**
     * Own stylesheet of a demo ("design: stylesheets: […]"): a reference to a demo
     * file ("file:design/custom.css") or a path in the file manager, e.g.
     * "files/design/custom.css". Such a file is created if it does not exist yet
     * and is never overwritten, so individual adjustments survive a new import.
     *
     * @return string|null the binary UUID
     */
    public function provideStylesheet(string $path): string|null
    {
        if (str_starts_with($path, 'file:')) {
            return $this->resolveReference($path);
        }

        $path = (string) preg_replace('#^'.preg_quote($this->uploadPath, '#').'/#', '', trim(str_replace('\\', '/', $path), '/'));

        if (!preg_match('#^[A-Za-z0-9._-]+(/[A-Za-z0-9._-]+)*\.css$#', $path) || \in_array('..', explode('/', $path), true)) {
            $this->warnings[] = \sprintf('The stylesheet "%s" is invalid (expected a CSS file inside "%s", e.g. "%s/design/custom.css").', $path, $this->uploadPath, $this->uploadPath);

            return null;
        }

        // The folder has to be public, so the browser can load the file
        $folder = explode('/', $path)[0];

        if (str_contains($path, '/') && !$this->filesStorage->fileExists($folder.'/.public')) {
            $this->filesStorage->write($folder.'/.public', '');
        }

        if (!$this->filesStorage->fileExists($path)) {
            $this->filesStorage->write($path, "/* Individuelle Anpassungen – wird nach dem Creuva-CSS geladen */\n");
        }

        return $this->filesStorage->get($path)?->getUuid()?->toBinary();
    }

    private function getDemoRoot(): string
    {
        return \dirname(__DIR__, 2).'/demo';
    }

    /**
     * Folder in the file manager (relative to the upload path) the demo files are
     * copied to: "creuva-demo/<demo name>" or the "filesFolder" of the definition.
     *
     * @param array<string, mixed> $definition
     *
     * @throws \InvalidArgumentException
     */
    public function getFilesFolder(string $demo, array $definition): string
    {
        if (!isset($definition['filesFolder'])) {
            return self::FILES_FOLDER.'/'.$this->getDemoName($demo);
        }

        $folder = trim(str_replace('\\', '/', (string) $definition['filesFolder']), '/');
        $folder = (string) preg_replace('#^'.preg_quote($this->uploadPath, '#').'/#', '', $folder);

        if ('' === $folder || !preg_match('#^[A-Za-z0-9._-]+(/[A-Za-z0-9._-]+)*$#', $folder) || \in_array('..', explode('/', $folder), true)) {
            throw new \InvalidArgumentException(\sprintf('The files folder "%s" is invalid (expected a folder inside "%s", e.g. "inhalte").', $definition['filesFolder'], $this->uploadPath));
        }

        // The demo source must not overlap with the target (e.g. files/my-demo)
        if ($this->isLocalDemo($demo)) {
            $source = substr($this->getDemoDir($demo), \strlen((string) realpath($this->projectDir.'/'.$this->uploadPath)) + 1);

            if (str_starts_with($folder.'/', $source.'/') || str_starts_with($source.'/', $folder.'/')) {
                throw new \InvalidArgumentException(\sprintf('The files folder "%s/%s" must not overlap with the demo folder "%s/%s".', $this->uploadPath, $folder, $this->uploadPath, $source));
            }
        }

        return $folder;
    }

    private function importFiles(string $target): void
    {
        $source = $this->demoDir.'/files';

        if (!is_dir($source)) {
            return;
        }

        // Make the folder public so downloads and SVG files can be served directly
        $publicFolder = str_starts_with($target, self::FILES_FOLDER.'/') ? self::FILES_FOLDER : $target;

        if (!$this->filesStorage->fileExists($publicFolder.'/.public')) {
            $this->filesStorage->write($publicFolder.'/.public', '');
        }

        foreach ((new Finder())->files()->in($source)->sortByName() as $file) {
            $path = $target.'/'.str_replace('\\', '/', $file->getRelativePathname());
            $this->filesStorage->write($path, $file->getContents());

            $uuid = $this->filesStorage->get($path)?->getUuid();

            if (null !== $uuid) {
                $this->refs['file'][str_replace('\\', '/', $file->getRelativePathname())] = $uuid->toBinary();
            }
        }
    }

    /**
     * @param array<string, mixed> $form
     */
    private function importForm(string $key, array $form): void
    {
        $fields = $form['fields'] ?? [];
        unset($form['fields']);

        $id = $this->insert('tl_form', $this->resolveRow($form + ['alias' => $key]) ?? []);
        $this->refs['form'][$key] = $id;

        foreach ($fields as $i => $field) {
            $this->insert('tl_form_field', ($this->resolveRow($field) ?? []) + ['pid' => $id, 'sorting' => ($i + 1) * 128]);
        }
    }

    /**
     * @param array<string, mixed> $archive
     */
    private function importNewsArchive(string $key, array $archive): void
    {
        $items = $archive['items'] ?? [];
        unset($archive['items']);

        $id = $this->insert('tl_news_archive', $this->resolveRow($archive) ?? []);
        $this->refs['news_archive'][$key] = $id;

        $time = strtotime('today 10:00');

        foreach ($items as $i => $item) {
            unset($item['elements']);

            // Dates relative to the import date ("-12 days")
            $date = isset($item['date']) ? strtotime((string) $item['date'], $time) : $time - $i * 86400 * 7;
            unset($item['date']);

            $data = $this->resolveRow($item) ?? [];
            $data['headline'] = (string) $item['headline']; // plain text in tl_news
            $data += ['pid' => $id, 'date' => $date, 'time' => $date, 'published' => 1, 'source' => 'default'];

            if (!empty($data['singleSRC'])) {
                $data += ['addImage' => 1];
            }

            $this->refs['news'][$item['alias']] = $this->insert('tl_news', $data);
        }
    }

    /**
     * Creates an event calendar. The date of an event is a strtotime() string
     * ("2026-12-05", "5 December" = current year, "+3 days"), the optional
     * "startTime" and "endTime" are given as "HH:MM".
     *
     * @param array<string, mixed> $calendar
     */
    private function importEventCalendar(string $key, array $calendar): void
    {
        $items = $calendar['items'] ?? [];
        unset($calendar['items']);

        $id = $this->insert('tl_calendar', $this->resolveRow($calendar) ?? []);
        $this->refs['calendar'][$key] = $id;

        foreach ($items as $item) {
            $date = strtotime((string) ($item['date'] ?? 'today'), strtotime('today'));

            if (false === $date) {
                $this->warnings[] = \sprintf('The date "%s" of the demo event "%s" is invalid, the event has been skipped.', $item['date'], $item['title'] ?? '');
                continue;
            }

            $startDate = strtotime(date('Y-m-d', $date));
            $endDate = isset($item['endDate']) ? strtotime(date('Y-m-d', strtotime((string) $item['endDate'], $startDate))) : null;
            $startTime = $item['startTime'] ?? null;
            $endTime = $item['endTime'] ?? $startTime;

            unset($item['elements'], $item['date'], $item['endDate'], $item['startTime'], $item['endTime']);

            // Same values as tl_calendar_events::adjustTime() stores
            if (null !== $startTime) {
                $times = [
                    'addTime' => 1,
                    'startTime' => strtotime(date('Y-m-d', $startDate).' '.$startTime),
                    'endTime' => strtotime(date('Y-m-d', $endDate ?? $startDate).' '.$endTime),
                ];
            } else {
                $times = [
                    'startTime' => $startDate,
                    'endTime' => strtotime('+1 day', $endDate ?? $startDate) - 1,
                ];
            }

            $data = $this->resolveRow($item) ?? [];
            $data['title'] = (string) $item['title']; // plain text in tl_calendar_events
            $data += $times + ['pid' => $id, 'startDate' => $startDate, 'endDate' => $endDate, 'published' => 1, 'source' => 'default'];

            if (!empty($data['singleSRC'])) {
                $data += ['addImage' => 1];
            }

            $this->refs['event'][$item['alias']] = $this->insert('tl_calendar_events', $data);
        }
    }

    /**
     * @param list<array<string, mixed>> $pages
     */
    private function importPageTree(array $pages, int $pid): void
    {
        foreach ($pages as $i => $page) {
            $children = $page['children'] ?? [];
            unset($page['children'], $page['articles']);

            $data = $this->resolveRow($page) ?? [];
            $data += ['pid' => $pid, 'sorting' => ($i + 1) * 128, 'type' => 'regular', 'published' => 1];

            $this->refs['page'][$data['alias']] = $this->insert('tl_page', $data);

            $this->importPageTree($children, $this->refs['page'][$data['alias']]);
        }
    }

    /**
     * @param list<array<string, mixed>> $pages
     */
    private function importArticles(array $pages): void
    {
        foreach ($pages as $page) {
            $pageId = $this->refs['page'][$page['alias']];

            foreach ($page['articles'] ?? [] as $i => $article) {
                $elements = $article['elements'] ?? [];
                unset($article['elements']);

                $data = $this->resolveRow($article) ?? [];
                $data += [
                    'pid' => $pageId,
                    'sorting' => ($i + 1) * 128,
                    'inColumn' => 'main',
                    'published' => 1,
                    'alias' => $this->uniqueAlias('tl_article', $page['alias'].'-'.($this->slug((string) ($article['title'] ?? '')) ?: $i + 1)),
                ];

                $this->importElements($elements, $this->insert('tl_article', $data), 'tl_article');
            }

            $this->importArticles($page['children'] ?? []);
        }
    }

    /**
     * @param list<array<string, mixed>> $elements
     */
    private function importElements(array $elements, int $pid, string $ptable): void
    {
        foreach ($elements as $i => $element) {
            $children = $element['children'] ?? [];
            unset($element['children']);

            $data = $this->resolveRow($element);

            // Skip elements whose references could not be resolved (e.g. news module without news bundle)
            if (null === $data) {
                continue;
            }

            $id = $this->insert('tl_content', $data + ['pid' => $pid, 'ptable' => $ptable, 'sorting' => ($i + 1) * 128]);

            if ($children) {
                $this->importElements($children, $id, 'tl_content');
            }
        }
    }

    /**
     * @param array<string, mixed> $row
     *
     * @return array<string, mixed>|null null if a reference cannot be resolved
     */
    private function resolveRow(array $row): array|null
    {
        $data = [];

        foreach ($row as $field => $value) {
            try {
                $data[$field] = match ($field) {
                    'headline', 'sectionHeadline' => serialize(\is_array($value) ? $value : ['unit' => 'h2', 'value' => (string) $value]),
                    'cssID' => serialize(\is_array($value) ? $value : ['', (string) $value]),
                    'size', 'imgSize' => $this->resolveSize($value) ?? '',
                    default => $this->resolveValue($value),
                };
            } catch (\OutOfBoundsException $e) {
                $this->warnings[] = $e->getMessage();

                return null;
            }
        }

        return $data;
    }

    /**
     * Resolves an image size ("image_size:card", [800, 600, crop] or
     * ['', '', 'image_size:card']). A missing image size is removed with a
     * warning instead of skipping the record.
     */
    public function resolveSize(mixed $value): string|null
    {
        if (\is_string($value) && str_starts_with($value, 'image_size:')) {
            $value = ['', '', $value];
        }

        try {
            return \is_array($value) ? $this->resolveValue($value) : (null === $value ? null : (string) $value);
        } catch (\OutOfBoundsException) {
            $this->warnings[] = \sprintf('The demo image size "%s" does not exist, the default size has been used.', implode(', ', array_map(strval(...), $value)));

            return null;
        }
    }

    private function resolveValue(mixed $value): mixed
    {
        if (\is_bool($value)) {
            return $value ? 1 : 0;
        }

        if (\is_array($value)) {
            return serialize(array_map(fn ($v) => \is_array($v) ? $v : $this->resolveValue($v), $value));
        }

        if (!\is_string($value) || !preg_match('/^(file|page|module|form|news_archive|calendar|image_size):(.+)$/', $value, $matches)) {
            return $value;
        }

        [, $type, $key] = $matches;

        if (!isset($this->refs[$type][$key])) {
            throw new \OutOfBoundsException(\sprintf('Demo reference "%s" could not be resolved, the record has been skipped.', $value));
        }

        $resolved = $this->refs[$type][$key];

        // IDs in serialized arrays are stored as strings
        return \is_int($resolved) ? (string) $resolved : $resolved;
    }

    /**
     * @param array<string, mixed> $data
     */
    private function insert(string $table, array $data): int
    {
        $this->connection->insert($table, $data + ['tstamp' => time()]);

        return (int) $this->connection->lastInsertId();
    }

    private function tableExists(string $table): bool
    {
        return $this->connection->createSchemaManager()->tablesExist([$table]);
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
        $text = strtr($text, ['ä' => 'ae', 'ö' => 'oe', 'ü' => 'ue', 'ß' => 'ss', 'Ä' => 'ae', 'Ö' => 'oe', 'Ü' => 'ue']);

        return strtolower(trim((string) preg_replace('/[^A-Za-z0-9]+/', '-', $text), '-'));
    }
}
