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

/**
 * Deletes a single website of a multisite installation so it can be imported
 * again (creuva:install --replace), without touching the other websites:
 *
 *  - the website roots with their pages, articles and content elements
 *  - the theme with the given name and the themes whose layouts the website uses,
 *    with their layouts, front end modules and image sizes
 *  - the forms, news archives and event calendars that only this website uses
 *    (referenced by its content elements or by the modules of its theme)
 *
 * A theme, form, news archive or calendar that another website still uses is
 * kept. The files in the file manager are kept as well: a demo import overwrites
 * them and keeps their UUIDs, so other references stay valid.
 */
class SiteReplacer
{
    public function __construct(private readonly Connection $connection)
    {
    }

    /**
     * Website roots with the given domain and language (as checked by creuva:install).
     *
     * @return array<int, string> root page ID => title
     */
    public function findRootPages(string $dns, string $language): array
    {
        $rows = $this->connection->fetchAllAssociative(
            "SELECT id, title FROM tl_page WHERE type IN ('root', 'rootfallback') AND dns = ? AND language = ? ORDER BY sorting",
            [$dns, $language],
        );

        return array_column(array_map(static fn (array $row): array => ['id' => (int) $row['id'], 'title' => (string) $row['title']], $rows), 'title', 'id');
    }

    /**
     * @param array<int, string> $rootPages root page ID => title
     */
    public function plan(array $rootPages, string $themeName): ReplacePlan
    {
        $plan = new ReplacePlan();
        $plan->rootPages = $rootPages;

        // Pages of the website roots (all levels)
        $pageIds = $pids = array_keys($rootPages);

        while ($pids = $this->fetchIds('tl_page', 'pid', $pids)) {
            $pageIds = [...$pageIds, ...$pids];
        }

        $plan->add('tl_page', $pageIds);

        $articleIds = $this->fetchIds('tl_article', 'pid', $pageIds);
        $plan->add('tl_article', $articleIds);

        // Themes: the one with the given name and the ones of the layouts of the website
        $themes = [];
        $namedTheme = $this->connection->fetchOne('SELECT id FROM tl_theme WHERE name = ?', [$themeName]);

        if (false !== $namedTheme) {
            $themes[(int) $namedTheme] = true;
        }

        if ($pageIds) {
            $layoutColumns = array_filter(['layout', $this->hasColumn('tl_page', 'subpageLayout') ? 'subpageLayout' : null]);

            foreach ($layoutColumns as $column) {
                foreach ($this->connection->fetchFirstColumn("SELECT DISTINCT l.pid FROM tl_page p INNER JOIN tl_layout l ON l.id = p.$column WHERE p.id IN (?)", [$pageIds], [ArrayParameterType::INTEGER]) as $themeId) {
                    $themes[(int) $themeId] ??= false;
                }
            }
        }

        $contentIds = $this->fetchContent(['tl_article' => $articleIds]);

        foreach ($themes as $themeId => $isNamed) {
            $name = (string) $this->connection->fetchOne('SELECT name FROM tl_theme WHERE id = ?', [$themeId]);

            if ($usage = $this->getOtherThemeUsage($themeId, $pageIds, $contentIds)) {
                if ($isNamed) {
                    $plan->errors[] = \sprintf('The theme "%s" (ID %d) is also used by %s. Use --theme-name to choose a different name.', $name, $themeId, $usage);
                } else {
                    $plan->warnings[] = \sprintf('The theme "%s" (ID %d) is kept, it is also used by %s.', $name, $themeId, $usage);
                }

                continue;
            }

            $plan->themes[$themeId] = $name;
        }

        $themeIds = array_keys($plan->themes);
        $plan->add('tl_theme', $themeIds);
        $plan->add('tl_layout', $this->fetchIds('tl_layout', 'pid', $themeIds));

        $imageSizeIds = $this->fetchIds('tl_image_size', 'pid', $themeIds);
        $plan->add('tl_image_size', $imageSizeIds);
        $plan->add('tl_image_size_item', $this->fetchIds('tl_image_size_item', 'pid', $imageSizeIds));

        $modules = $themeIds ? $this->connection->fetchAllAssociative('SELECT * FROM tl_module WHERE pid IN (?)', [$themeIds], [ArrayParameterType::INTEGER]) : [];
        $plan->add('tl_module', array_map(static fn (array $row): int => (int) $row['id'], $modules));

        // News archives and calendars of the news and event modules of the theme
        $this->planArchives($plan, 'tl_news_archive', 'tl_news', 'news_archives', $modules, $themeIds, 'news archive');
        $this->planArchives($plan, 'tl_calendar', 'tl_calendar_events', 'cal_calendar', $modules, $themeIds, 'event calendar');

        $contentIds = $this->fetchContent([
            'tl_article' => $articleIds,
            'tl_news' => $plan->get('tl_news'),
            'tl_calendar_events' => $plan->get('tl_calendar_events'),
        ]);

        $plan->add('tl_content', $contentIds);

        // Forms of the form elements and form modules
        $formIds = [];

        if ($contentIds) {
            $formIds = $this->connection->fetchFirstColumn("SELECT DISTINCT form FROM tl_content WHERE type = 'form' AND id IN (?)", [$contentIds], [ArrayParameterType::INTEGER]);
        }

        foreach ($modules as $module) {
            if ('form' === $module['type'] && isset($module['form'])) {
                $formIds[] = $module['form'];
            }
        }

        foreach (array_unique(array_filter(array_map(intval(...), $formIds))) as $formId) {
            $usedByContent = (int) $this->connection->fetchOne(
                "SELECT COUNT(*) FROM tl_content WHERE type = 'form' AND form = ?".($contentIds ? ' AND id NOT IN (?)' : ''),
                $contentIds ? [$formId, $contentIds] : [$formId],
                $contentIds ? [ParameterType::INTEGER, ArrayParameterType::INTEGER] : [ParameterType::INTEGER],
            );

            $usedByModule = (int) $this->connection->fetchOne(
                "SELECT COUNT(*) FROM tl_module WHERE type = 'form' AND form = ?".($themeIds ? ' AND pid NOT IN (?)' : ''),
                $themeIds ? [$formId, $themeIds] : [$formId],
                $themeIds ? [ParameterType::INTEGER, ArrayParameterType::INTEGER] : [ParameterType::INTEGER],
            );

            if ($usedByContent || $usedByModule) {
                $plan->warnings[] = \sprintf('The form "%s" (ID %d) is kept, it is also used by another website.', $this->connection->fetchOne('SELECT title FROM tl_form WHERE id = ?', [$formId]), $formId);
            } else {
                $plan->add('tl_form', [$formId]);
            }
        }

        $plan->add('tl_form_field', $this->fetchIds('tl_form_field', 'pid', $plan->get('tl_form')));

        // Remove empty entries and tables that do not exist
        $plan->records = array_filter($plan->records);

        return $plan;
    }

    /**
     * Deletes the records of the plan in one transaction.
     */
    public function remove(ReplacePlan $plan): void
    {
        if ($plan->errors) {
            throw new \LogicException('The website cannot be replaced: '.implode(' ', $plan->errors));
        }

        $this->connection->transactional(function () use ($plan): void {
            foreach ($plan->records as $table => $ids) {
                foreach (array_chunk($ids, 500) as $chunk) {
                    $this->connection->executeStatement("DELETE FROM $table WHERE id IN (?)", [$chunk], [ArrayParameterType::INTEGER]);
                }
            }
        });
    }

    /**
     * Archives (news archives, calendars) of the modules of the theme, with their
     * items, unless a module of another theme uses them as well.
     *
     * @param list<array<string, mixed>> $modules
     * @param list<int>                  $themeIds
     */
    private function planArchives(ReplacePlan $plan, string $table, string $itemTable, string $field, array $modules, array $themeIds, string $label): void
    {
        if (!$this->tableExists($table) || !$this->hasColumn('tl_module', $field)) {
            return;
        }

        $archiveIds = [];

        foreach ($modules as $module) {
            $archiveIds = [...$archiveIds, ...$this->unserializeIds($module[$field] ?? null)];
        }

        $archiveIds = array_values(array_unique($archiveIds));

        if (!$archiveIds) {
            return;
        }

        // Archives that modules of other themes use
        $used = [];
        $otherModules = $this->connection->fetchFirstColumn(
            "SELECT $field FROM tl_module WHERE $field IS NOT NULL AND $field != ''".($themeIds ? ' AND pid NOT IN (?)' : ''),
            $themeIds ? [$themeIds] : [],
            $themeIds ? [ArrayParameterType::INTEGER] : [],
        );

        foreach ($otherModules as $value) {
            $used = [...$used, ...$this->unserializeIds($value)];
        }

        foreach ($archiveIds as $archiveId) {
            if (false === $this->connection->fetchOne("SELECT id FROM $table WHERE id = ?", [$archiveId])) {
                continue;
            }

            if (\in_array($archiveId, $used, true)) {
                $plan->warnings[] = \sprintf('The %s "%s" (ID %d) is kept, it is also used by another website.', $label, $this->connection->fetchOne("SELECT title FROM $table WHERE id = ?", [$archiveId]), $archiveId);

                continue;
            }

            $plan->add($table, [$archiveId]);
        }

        if ($this->tableExists($itemTable)) {
            $plan->add($itemTable, $this->fetchIds($itemTable, 'pid', $plan->get($table)));
        }
    }

    /**
     * Describes where else a theme is used (pages of other websites with one of its
     * layouts, content elements of other websites with one of its modules), or an
     * empty string if it is not used elsewhere.
     *
     * @param list<int> $pageIds
     * @param list<int> $contentIds
     */
    private function getOtherThemeUsage(int $themeId, array $pageIds, array $contentIds): string
    {
        $usage = [];
        $layoutIds = $this->fetchIds('tl_layout', 'pid', [$themeId]);

        if ($layoutIds) {
            $condition = 'layout IN (:layouts)';

            if ($this->hasColumn('tl_page', 'subpageLayout')) {
                $condition = '(layout IN (:layouts) OR subpageLayout IN (:layouts))';
            }

            $pages = $this->connection->fetchFirstColumn(
                "SELECT title FROM tl_page WHERE $condition".($pageIds ? ' AND id NOT IN (:pages)' : '').' ORDER BY id LIMIT 3',
                $pageIds ? ['layouts' => $layoutIds, 'pages' => $pageIds] : ['layouts' => $layoutIds],
                $pageIds ? ['layouts' => ArrayParameterType::INTEGER, 'pages' => ArrayParameterType::INTEGER] : ['layouts' => ArrayParameterType::INTEGER],
            );

            if ($pages) {
                $usage[] = \sprintf('the page(s) "%s"', implode('", "', $pages));
            }
        }

        $moduleIds = $this->fetchIds('tl_module', 'pid', [$themeId]);

        if ($moduleIds) {
            $count = (int) $this->connection->fetchOne(
                "SELECT COUNT(*) FROM tl_content WHERE type = 'module' AND module IN (:modules)".($contentIds ? ' AND id NOT IN (:content)' : ''),
                $contentIds ? ['modules' => $moduleIds, 'content' => $contentIds] : ['modules' => $moduleIds],
                $contentIds ? ['modules' => ArrayParameterType::INTEGER, 'content' => ArrayParameterType::INTEGER] : ['modules' => ArrayParameterType::INTEGER],
            );

            if ($count) {
                $usage[] = \sprintf('%d content element(s) of other websites', $count);
            }
        }

        return implode(' and ', $usage);
    }

    /**
     * IDs of the content elements of the given parents, including nested elements.
     *
     * @param array<string, list<int>> $parents ptable => parent IDs
     *
     * @return list<int>
     */
    private function fetchContent(array $parents): array
    {
        $ids = [];

        foreach ($parents as $ptable => $parentIds) {
            while ($parentIds) {
                $found = array_map(intval(...), $this->connection->fetchFirstColumn(
                    'SELECT id FROM tl_content WHERE ptable = ? AND pid IN (?)',
                    [$ptable, $parentIds],
                    [ParameterType::STRING, ArrayParameterType::INTEGER],
                ));

                $ids = [...$ids, ...$found];
                $ptable = 'tl_content';
                $parentIds = $found;
            }
        }

        return array_values(array_unique($ids));
    }

    /**
     * @param list<int> $values
     *
     * @return list<int>
     */
    private function fetchIds(string $table, string $column, array $values): array
    {
        if (!$values || !$this->tableExists($table)) {
            return [];
        }

        return array_map(intval(...), $this->connection->fetchFirstColumn("SELECT id FROM $table WHERE $column IN (?)", [$values], [ArrayParameterType::INTEGER]));
    }

    /**
     * @return list<int>
     */
    private function unserializeIds(mixed $value): array
    {
        $ids = @unserialize((string) $value, ['allowed_classes' => false]);

        return \is_array($ids) ? array_values(array_filter(array_map(intval(...), $ids))) : [];
    }

    private function tableExists(string $table): bool
    {
        return $this->connection->createSchemaManager()->tablesExist([$table]);
    }

    private function hasColumn(string $table, string $column): bool
    {
        return \in_array(strtolower($column), array_map(strtolower(...), array_keys($this->connection->createSchemaManager()->listTableColumns($table))), true);
    }
}
