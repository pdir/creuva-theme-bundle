<?php

declare(strict_types=1);

/*
 * Creuva theme bundle for Contao Open Source CMS
 *
 * Copyright (C) pdir GmbH <develop@pdir.de>
 */

namespace Pdir\CreuvaThemeBundle\Installer;

use Craffft\CssStyleSelectorBundle\Models\CssStyleSelectorModel;
use Craffft\CssStyleSelectorBundle\Repository\CssStyleRepository;
use Doctrine\DBAL\Connection;
use Symfony\Component\Yaml\Yaml;

/**
 * Imports the Creuva groups of config/css-styles.yaml into the tables of the
 * CSS style selector (tl_css_style_group, tl_css_style_selector), so editors
 * can change them and add their own groups in the back end.
 *
 * Only missing groups and styles are added: changes of the editors (titles,
 * element types, removed styles …) are never overwritten. The imported groups
 * are recognized by their identifier (<section>.<group>), the styles of a
 * group by their CSS class.
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

        if (!$schemaManager->tablesExist(['tl_css_style_group', 'tl_css_style_selector'])) {
            return false;
        }

        // Added by contao/dca/tl_css_style_group.php
        return isset(array_change_key_case($schemaManager->listTableColumns('tl_css_style_group'))['creuvaidentifier']);
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

        $this->connection->transactional(function () use (&$count): void {
            foreach ($this->getGroups() as $identifier => $group) {
                $groupId = $this->connection->fetchOne('SELECT id FROM tl_css_style_group WHERE creuvaIdentifier = ?', [$identifier]);

                if (false === $groupId) {
                    $this->connection->insert('tl_css_style_group', [
                        'tstamp' => time(),
                        'title' => $group['title'],
                        'description' => $group['description'],
                        'multiple' => 0,
                        'sorting' => $group['sorting'],
                        'creuvaIdentifier' => $identifier,
                    ]);

                    $groupId = $this->connection->lastInsertId();
                    ++$count['groups'];
                }

                $count['styles'] += $this->importStyles((int) $groupId, $group);
            }
        });

        // The repository caches the styles of the request
        $this->repository->reset();

        return $count;
    }

    /**
     * Adds the styles whose CSS class is not in the group yet.
     *
     * @param array{styles: array<string, string>, types: array<string, list<string>|true>} $group
     */
    private function importStyles(int $groupId, array $group): int
    {
        $existing = $this->connection->fetchFirstColumn('SELECT cssClasses FROM tl_css_style_selector WHERE pid = ?', [$groupId]);
        $sorting = (int) $this->connection->fetchOne('SELECT MAX(sorting) FROM tl_css_style_selector WHERE pid = ?', [$groupId]);
        $added = 0;

        foreach ($group['styles'] as $class => $label) {
            if (\in_array($class, $existing, true)) {
                continue;
            }

            $sorting += 128;

            $data = [
                'pid' => $groupId,
                'tstamp' => time(),
                'sorting' => $sorting,
                'styleDesignation' => $label,
                'cssClasses' => $class,
            ];

            // Disabled everywhere except for the types of the group
            foreach (CssStyleSelectorModel::getAvailableTypes() as $type) {
                $data[CssStyleSelectorModel::getDisableField($type)] = isset($group['types'][$type]) ? 0 : 1;
            }

            if (\is_array($elements = $group['types'][CssStyleSelectorModel::TYPE_CONTENT] ?? null)) {
                $data['limitContentElements'] = 1;
                $data['contentElements'] = serialize($elements);
            }

            $this->connection->insert('tl_css_style_selector', $data);
            ++$added;
        }

        return $added;
    }

    /**
     * Groups of the YAML file keyed by their identifier.
     *
     * @return array<string, array{title: string, description: string, sorting: int, styles: array<string, string>, types: array<string, list<string>|true>}>
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
                    'title' => $section['title'].' › '.$group['title'],
                    'description' => (string) ($group['description'] ?? $section['description'] ?? ''),
                    // Sections first, then the groups of a section
                    'sorting' => (int) ($section['sorting'] ?? 0) * 100 + (int) ($group['sorting'] ?? 0),
                    'styles' => array_combine(array_map(strval(...), array_keys($group['styles'] ?? [])), array_map(strval(...), $group['styles'] ?? [])),
                    'types' => $types,
                ];
            }
        }

        return $groups;
    }
}
