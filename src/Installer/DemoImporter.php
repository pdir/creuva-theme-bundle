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
 * from a YAML definition in demo/<name>/demo.yaml.
 *
 * Values in the definition are resolved as follows:
 *
 *   file:<path>          binary UUID of a demo file (multiSRC: list of file:…)
 *   page:<alias>         ID of a demo page
 *   module:<key>         ID of a demo front end module
 *   form:<key>           ID of a demo form
 *   news_archive:<key>   ID of a demo news archive
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
     * @return array<string, mixed>
     */
    public function load(string $name): array
    {
        $file = $this->getDemoRoot().'/'.$name.'/demo.yaml';

        if (!preg_match('/^[a-z0-9-]+$/', $name) || !is_file($file)) {
            throw new \InvalidArgumentException(\sprintf('Unknown demo "%s".', $name));
        }

        return Yaml::parseFile($file);
    }

    /**
     * Copies the demo files to the file manager.
     *
     * @param array<string, mixed> $definition
     */
    public function prepare(string $name, array $definition, string $language = 'de'): void
    {
        $this->refs = [];
        $this->warnings = [];
        $this->demoDir = $this->getDemoRoot().'/'.$name;

        $this->importFiles($name);

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

    private function getDemoRoot(): string
    {
        return \dirname(__DIR__, 2).'/demo';
    }

    private function importFiles(string $name): void
    {
        $source = $this->demoDir.'/files';

        if (!is_dir($source)) {
            return;
        }

        $target = self::FILES_FOLDER.'/'.$name;

        // Make the folder public so downloads and SVG files can be served directly
        if (!$this->filesStorage->fileExists(self::FILES_FOLDER.'/.public')) {
            $this->filesStorage->write(self::FILES_FOLDER.'/.public', '');
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
                    default => $this->resolveValue($value),
                };
            } catch (\OutOfBoundsException $e) {
                $this->warnings[] = $e->getMessage();

                return null;
            }
        }

        return $data;
    }

    private function resolveValue(mixed $value): mixed
    {
        if (\is_bool($value)) {
            return $value ? 1 : 0;
        }

        if (\is_array($value)) {
            return serialize(array_map(fn ($v) => \is_array($v) ? $v : $this->resolveValue($v), $value));
        }

        if (!\is_string($value) || !preg_match('/^(file|page|module|form|news_archive):(.+)$/', $value, $matches)) {
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
