<?php

declare(strict_types=1);

/*
 * Creuva theme bundle for Contao Open Source CMS
 *
 * Copyright (C) pdir GmbH <develop@pdir.dev>
 */

namespace Pdir\CreuvaThemeBundle\Installer;

use Contao\StringUtil;
use Craffft\CssStyleSelectorBundle\Migration\StyleCategoryMigration;
use Craffft\CssStyleSelectorBundle\Models\CssStyleSelectorModel;
use Craffft\CssStyleSelectorBundle\Repository\CssStyleRepository;
use Doctrine\DBAL\Connection;
use Symfony\Component\Yaml\Yaml;

/**
 * Imports the Creuva sections and groups of config/css-styles.yaml into the
 * tables of the CSS style selector (tl_css_style_category, tl_css_style_group
 * with the styles as rows), so editors can change them and add their own
 * categories and groups in the back end.
 *
 * Only missing categories, groups, styles and element types (of groups limited
 * to certain element types) are added: changes of the editors
 * (titles, element types, removed styles …) are never overwritten. The
 * imported categories and groups are recognized by their identifier (<section>
 * and <section>.<group>), the styles of a group by their CSS class.
 */
class CssStyleImporter
{
    public const CONFIG = __DIR__.'/../../config/css-styles.yaml';

    /**
     * Option in the YAML file → style type that is enabled with it.
     */
    private const TYPES = [
        'contentElements' => CssStyleSelectorModel::TYPE_CONTENT,
        'article' => CssStyleSelectorModel::TYPE_ARTICLE,
        'module' => CssStyleSelectorModel::TYPE_MODULE,
    ];

    public function __construct(
        private readonly Connection $connection,
        private readonly CssStyleRepository $repository,
    ) {
    }

    public function isAvailable(): bool
    {
        $schemaManager = $this->connection->createSchemaManager();

        if (!$schemaManager->tablesExist(['tl_css_style_category', 'tl_css_style_group'])) {
            return false;
        }

        $categoryColumns = array_change_key_case($schemaManager->listTableColumns('tl_css_style_category'));
        $groupColumns = array_change_key_case($schemaManager->listTableColumns('tl_css_style_group'));

        // creuvaIdentifier is added by Pdir\CreuvaThemeBundle\EventListener\CssStyleGroupIdentifierListener
        if (!isset($categoryColumns['creuvaidentifier'], $groupColumns['creuvaidentifier'], $groupColumns['pid'], $groupColumns['styles'])) {
            return false;
        }

        // Wait until the StyleRowsMigration has moved the styles of the former table into their groups
        return !$schemaManager->tablesExist(['tl_css_style_selector'])
            || !$this->connection->fetchOne('SELECT COUNT(*) FROM tl_css_style_group g WHERE g.styles IS NULL AND EXISTS (SELECT 1 FROM tl_css_style_selector s WHERE s.pid = g.id)');
    }

    /**
     * Whether Creuva groups are not in a Creuva category yet, e.g. groups of
     * the version before the categories, which were moved into the category
     * "General" by the CSS style selector.
     */
    public function hasUncategorizedGroups(): bool
    {
        return $this->isAvailable() && [] !== $this->findUncategorizedGroups();
    }

    /**
     * Moves the Creuva groups that are not in a Creuva category yet into the
     * category of their section and removes the section from the title
     * ("Abstände › Innenabstand" → "Innenabstand") unless an editor changed it.
     *
     * @return int number of moved groups
     */
    public function assignCategories(): int
    {
        if (!$this->isAvailable()) {
            return 0;
        }

        $groups = $this->getGroups();
        $moved = 0;

        $this->connection->transactional(function () use ($groups, &$moved): void {
            foreach ($this->findUncategorizedGroups() as $row) {
                $group = $groups[$row['creuvaIdentifier']] ?? null;

                if (null === $group) {
                    continue;
                }

                $data = ['pid' => $this->getCategoryId($group['section'])];

                if ($row['title'] === $group['legacyTitle']) {
                    $data['title'] = $group['title'];
                }

                if ($row['description'] === $group['legacyDescription']) {
                    $data['description'] = $group['description'];
                }

                $this->connection->update('tl_css_style_group', $data, ['id' => $row['id']]);
                ++$moved;
            }

            // Remove the category "General" of the migration if it is empty now
            $this->connection->executeStatement(
                "DELETE FROM tl_css_style_category WHERE title = ? AND creuvaIdentifier = '' AND NOT EXISTS (SELECT 1 FROM tl_css_style_group g WHERE g.pid = tl_css_style_category.id)",
                [StyleCategoryMigration::CATEGORY_TITLE],
            );
        });

        $this->repository->reset();

        return $moved;
    }

    /**
     * Whether at least one Creuva group is in the database.
     */
    public function hasGroups(): bool
    {
        return $this->isAvailable() && \count($this->getMissingGroups()) < \count($this->getGroups());
    }

    /**
     * Identifiers of the Creuva groups that are not in the database yet.
     *
     * @return list<string>
     */
    public function getMissingGroups(): array
    {
        if (!$this->isAvailable()) {
            return [];
        }

        $existing = $this->connection->fetchFirstColumn("SELECT creuvaIdentifier FROM tl_css_style_group WHERE creuvaIdentifier != ''");

        return array_values(array_diff(array_keys($this->getGroups()), $existing));
    }

    /**
     * @return array{groups: int, styles: int} number of added records
     */
    public function import(): array
    {
        $count = ['groups' => 0, 'styles' => 0];

        if (!$this->isAvailable()) {
            return $count;
        }

        // Groups of the version before the categories
        $this->assignCategories();

        // Categories that lost their identifier (e.g. after the identifier column was dropped by a database update)
        foreach ($this->getSections() as $section => $config) {
            if (false === $this->connection->fetchOne('SELECT id FROM tl_css_style_category WHERE creuvaIdentifier = ?', [$section])) {
                $this->connection->executeStatement("UPDATE tl_css_style_category SET creuvaIdentifier = ? WHERE creuvaIdentifier = '' AND title = ? ORDER BY id LIMIT 1", [$section, $config['title']]);
            }
        }

        $this->connection->transactional(function () use (&$count): void {
            foreach ($this->getGroups() as $identifier => $group) {
                $groupId = $this->connection->fetchOne('SELECT id FROM tl_css_style_group WHERE creuvaIdentifier = ?', [$identifier]);

                if (false === $groupId) {
                    $this->connection->insert('tl_css_style_group', [
                        'pid' => $this->getCategoryId($group['section']),
                        'tstamp' => time(),
                        'title' => $group['title'],
                        'description' => $group['description'],
                        'multiple' => 0,
                        'sorting' => $group['sorting'],
                        'creuvaIdentifier' => $identifier,
                        ...$this->getPublication($group),
                    ]);

                    $groupId = $this->connection->lastInsertId();
                    ++$count['groups'];
                } else {
                    $this->addElementTypes((int) $groupId, $group);
                }

                $count['styles'] += $this->importStyles((int) $groupId, $group);
            }
        });

        // The repository caches the styles of the request
        $this->repository->reset();

        return $count;
    }

    /**
     * Returns the ID of the category of a section and adds the category if it
     * does not exist yet.
     */
    private function getCategoryId(string $section): int
    {
        $categoryId = $this->connection->fetchOne('SELECT id FROM tl_css_style_category WHERE creuvaIdentifier = ?', [$section]);

        if (false !== $categoryId) {
            return (int) $categoryId;
        }

        $config = $this->getSections()[$section];

        // A category with the title of the section but without identifier, e.g.
        // after the identifier column was dropped by a database update
        $categoryId = $this->connection->fetchOne("SELECT id FROM tl_css_style_category WHERE creuvaIdentifier = '' AND title = ? ORDER BY id LIMIT 1", [$config['title']]);

        if (false !== $categoryId) {
            $this->connection->update('tl_css_style_category', ['creuvaIdentifier' => $section], ['id' => $categoryId]);

            return (int) $categoryId;
        }

        $this->connection->insert('tl_css_style_category', [
            'tstamp' => time(),
            'sorting' => $config['sorting'],
            'title' => $config['title'],
            'description' => $config['description'],
            'creuvaIdentifier' => $section,
        ]);

        return (int) $this->connection->lastInsertId();
    }

    /**
     * Creuva groups whose category is not a Creuva category, but the category
     * "General" of the migration (or none at all). Groups an editor moved into
     * an own category are not touched.
     *
     * @return list<array{id: int|string, title: string, description: string, creuvaIdentifier: string}>
     */
    private function findUncategorizedGroups(): array
    {
        /** @var list<array{id: int|string, title: string, description: string, creuvaIdentifier: string}> */
        return $this->connection->fetchAllAssociative(
            "SELECT g.id, g.title, g.description, g.creuvaIdentifier
            FROM tl_css_style_group g
            LEFT JOIN tl_css_style_category c ON c.id = g.pid
            WHERE g.creuvaIdentifier != '' AND (c.id IS NULL OR (c.creuvaIdentifier = '' AND c.title = ?))",
            [StyleCategoryMigration::CATEGORY_TITLE],
        );
    }

    /**
     * Areas the group is published in and its content element types.
     *
     * @param array{types: array<string, list<string>|true>} $group
     *
     * @return array<string, int|string|null>
     */
    private function getPublication(array $group): array
    {
        $data = [];

        foreach (CssStyleSelectorModel::getAvailableTypes() as $type) {
            $data[CssStyleSelectorModel::getEnableField($type)] = isset($group['types'][$type]) ? 1 : 0;
        }

        // Element types of content elements and modules (none = all types)
        foreach (CssStyleSelectorModel::TYPE_RESTRICTIONS as $type => $field) {
            $types = $group['types'][$type] ?? null;
            $data[$field] = \is_array($types) ? serialize($types) : null;
        }

        return $data;
    }

    /**
     * Adds new element types of the YAML file to an existing group that is
     * limited to certain element types (e.g. after a theme update). Nothing is
     * removed, and groups without a limit (all types) stay unlimited.
     *
     * @param array{types: array<string, list<string>|true>} $group
     */
    private function addElementTypes(int $groupId, array $group): void
    {
        $row = $this->connection->fetchAssociative('SELECT * FROM tl_css_style_group WHERE id = ?', [$groupId]);
        $data = [];

        foreach (CssStyleSelectorModel::TYPE_RESTRICTIONS as $type => $field) {
            if (!\is_array($group['types'][$type] ?? null) || !\array_key_exists($field, (array) $row)) {
                continue;
            }

            $current = StringUtil::deserialize($row[$field], true);
            $new = array_values(array_diff($group['types'][$type], $current));

            if ($current && $new) {
                $data[$field] = serialize([...$current, ...$new]);
            }
        }

        if ($data) {
            $this->connection->update('tl_css_style_group', [...$data, 'tstamp' => time()], ['id' => $groupId]);
        }
    }

    /**
     * Adds the styles whose CSS class is not in the group yet at the end.
     *
     * @param array{styles: array<string, string>} $group
     */
    private function importStyles(int $groupId, array $group): int
    {
        $rows = StringUtil::deserialize($this->connection->fetchOne('SELECT styles FROM tl_css_style_group WHERE id = ?', [$groupId]), true);
        $existing = array_keys(CssStyleRepository::parseStyles($rows));
        $added = 0;

        foreach ($group['styles'] as $class => $label) {
            if (\in_array($class, $existing, true)) {
                continue;
            }

            $rows[] = ['key' => $class, 'value' => $label];
            ++$added;
        }

        if ($added) {
            $this->connection->update('tl_css_style_group', ['tstamp' => time(), 'styles' => serialize(array_values($rows))], ['id' => $groupId]);
        }

        return $added;
    }

    /**
     * Sections of the YAML file (categories) keyed by their identifier.
     *
     * @return array<string, array{title: string, description: string, sorting: int}>
     */
    private function getSections(): array
    {
        $sections = [];

        foreach (Yaml::parseFile(self::CONFIG) ?? [] as $sectionKey => $section) {
            $sections[(string) $sectionKey] = [
                'title' => (string) $section['title'],
                'description' => (string) ($section['description'] ?? ''),
                'sorting' => (int) ($section['sorting'] ?? 0),
            ];
        }

        return $sections;
    }

    /**
     * Groups of the YAML file keyed by their identifier.
     *
     * @return array<string, array{section: string, title: string, description: string, legacyTitle: string, legacyDescription: string, sorting: int, styles: array<string, string>, types: array<string, list<string>|true>}>
     */
    private function getGroups(): array
    {
        $groups = [];

        foreach (Yaml::parseFile(self::CONFIG) ?? [] as $sectionKey => $section) {
            foreach ($section['groups'] ?? [] as $groupKey => $group) {
                $types = [];

                foreach (self::TYPES as $option => $type) {
                    if (!empty($group[$option])) {
                        $types[$type] = \is_array($group[$option]) ? array_map(strval(...), array_values($group[$option])) : true;
                    }
                }

                $groups[$sectionKey.'.'.$groupKey] = [
                    'section' => (string) $sectionKey,
                    'title' => (string) $group['title'],
                    'description' => (string) ($group['description'] ?? ''),
                    // Title and description of the version before the categories
                    'legacyTitle' => $section['title'].' › '.$group['title'],
                    'legacyDescription' => (string) ($group['description'] ?? $section['description'] ?? ''),
                    'sorting' => (int) ($group['sorting'] ?? 0),
                    'styles' => array_combine(array_map(strval(...), array_keys($group['styles'] ?? [])), array_map(strval(...), $group['styles'] ?? [])),
                    'types' => $types,
                ];
            }
        }

        return $groups;
    }
}
