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
use Doctrine\DBAL\ParameterType;
use Symfony\Component\Yaml\Yaml;

/**
 * Adds the styleguide (styleguide/styleguide.yaml: all elements, template
 * variants, classes and modules of the theme) as a hidden page to an existing
 * website, independent of the demo. Its modules, form and news archive are
 * prefixed with "Styleguide – ", its files are copied to
 * files/creuva-demo/styleguide.
 */
class StyleguideInstaller
{
    public const ALIAS = 'styleguide';

    public const PREFIX = 'Styleguide – ';

    private const FILES_FOLDER = DemoImporter::FILES_FOLDER.'/styleguide';

    public function __construct(
        private readonly Connection $connection,
        private readonly DemoImporter $demoImporter,
    ) {
    }

    /**
     * ID of the Creuva theme the layout of the website root belongs to.
     */
    public function findThemeId(int $rootId): int|null
    {
        $themeId = $this->connection->fetchOne('SELECT l.pid FROM tl_page p INNER JOIN tl_layout l ON l.id = p.layout WHERE p.id = ? AND p.includeLayout = 1', [$rootId]);

        return false === $themeId ? null : (int) $themeId;
    }

    /**
     * ID of an existing styleguide page in the website.
     */
    public function findPage(int $rootId): int|null
    {
        $id = $this->connection->fetchOne("SELECT id FROM tl_page WHERE alias = ? AND pid = ? AND type = 'regular'", [self::ALIAS, $rootId]);

        return false === $id ? null : (int) $id;
    }

    /**
     * @return array{pageId: int, warnings: list<string>}
     */
    public function install(int $rootId, int $themeId, string $language = 'de'): array
    {
        $definition = Yaml::parseFile($this->getDir().'/styleguide.yaml');

        return $this->connection->transactional(function () use ($definition, $rootId, $themeId, $language): array {
            $this->demoImporter->prepareDirectory($this->getDir(), self::FILES_FOLDER, $definition, $language);
            $this->demoImporter->importImageSizes($definition, $themeId, true);

            // Below the existing pages of the website
            $sorting = (int) $this->connection->fetchOne('SELECT MAX(sorting) FROM tl_page WHERE pid = ?', [$rootId]);
            $pages = $this->demoImporter->importPages($definition, $rootId, $sorting);

            $this->demoImporter->importModules($definition, $themeId);
            $this->demoImporter->importContent($definition);

            return ['pageId' => $pages[self::ALIAS], 'warnings' => $this->demoImporter->getWarnings()];
        });
    }

    /**
     * Deletes an existing styleguide (pages with articles and content elements,
     * modules, form, news archive) before it is installed again.
     */
    public function remove(int $pageId, int $themeId): void
    {
        $this->connection->transactional(function () use ($pageId, $themeId): void {
            $pageIds = $pids = [$pageId];

            while ($pids = $this->fetchIds('tl_page', 'pid', $pids)) {
                $pageIds = [...$pageIds, ...$pids];
            }

            $this->deleteContent('tl_article', $this->fetchIds('tl_article', 'pid', $pageIds));
            $this->connection->executeStatement('DELETE FROM tl_article WHERE pid IN (?)', [$pageIds], [ArrayParameterType::INTEGER]);
            $this->connection->executeStatement('DELETE FROM tl_page WHERE id IN (?)', [$pageIds], [ArrayParameterType::INTEGER]);

            $this->connection->executeStatement('DELETE FROM tl_module WHERE pid = ? AND name LIKE ?', [$themeId, self::PREFIX.'%']);

            foreach ($this->connection->fetchFirstColumn('SELECT id FROM tl_form WHERE title LIKE ?', [self::PREFIX.'%']) as $formId) {
                $this->connection->delete('tl_form_field', ['pid' => $formId]);
                $this->connection->delete('tl_form', ['id' => $formId]);
            }

            if ($this->connection->createSchemaManager()->tablesExist(['tl_news_archive'])) {
                foreach ($this->connection->fetchFirstColumn('SELECT id FROM tl_news_archive WHERE title LIKE ?', [self::PREFIX.'%']) as $archiveId) {
                    $newsIds = $this->fetchIds('tl_news', 'pid', [(int) $archiveId]);
                    $this->deleteContent('tl_news', $newsIds);
                    $this->connection->delete('tl_news', ['pid' => $archiveId]);
                    $this->connection->delete('tl_news_archive', ['id' => $archiveId]);
                }
            }
        });
    }

    private function getDir(): string
    {
        return \dirname(__DIR__, 2).'/styleguide';
    }

    /**
     * Deletes the content elements of the given parents (with nested elements).
     *
     * @param list<int> $parentIds
     */
    private function deleteContent(string $ptable, array $parentIds): void
    {
        while ($parentIds) {
            $ids = $this->connection->fetchFirstColumn('SELECT id FROM tl_content WHERE ptable = ? AND pid IN (?)', [$ptable, $parentIds], [ParameterType::STRING, ArrayParameterType::INTEGER]);
            $this->connection->executeStatement('DELETE FROM tl_content WHERE ptable = ? AND pid IN (?)', [$ptable, $parentIds], [ParameterType::STRING, ArrayParameterType::INTEGER]);

            $ptable = 'tl_content';
            $parentIds = array_map(intval(...), $ids);
        }
    }

    /**
     * @param list<int> $values
     *
     * @return list<int>
     */
    private function fetchIds(string $table, string $column, array $values): array
    {
        if (!$values) {
            return [];
        }

        return array_map(intval(...), $this->connection->fetchFirstColumn("SELECT id FROM $table WHERE $column IN (?)", [$values], [ArrayParameterType::INTEGER]));
    }
}
