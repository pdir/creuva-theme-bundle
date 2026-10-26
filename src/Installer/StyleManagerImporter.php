<?php

declare(strict_types=1);

/*
 * Creuva theme bundle for Contao Open Source CMS
 *
 * Copyright (C) pdir GmbH <develop@pdir.de>
 */

namespace Pdir\CreuvaThemeBundle\Installer;

use Doctrine\DBAL\Connection;
use Symfony\Component\Yaml\Yaml;

/**
 * Imports the Creuva groups of config/style-manager.yaml into the tables of the
 * Style Manager (tl_style_manager_archive, tl_style_manager), so editors can
 * change them and add their own groups in the back end.
 *
 * Only missing archives, groups and classes are added: changes of the editors
 * (titles, element types, removed classes …) are never overwritten.
 */
class StyleManagerImporter
{
    /**
     * Fields of a group that are stored serialized.
     */
    private const SERIALIZED = ['contentElements', 'modules', 'formFields'];

    public function __construct(private readonly Connection $connection)
    {
    }

    public function isAvailable(): bool
    {
        return $this->connection->createSchemaManager()->tablesExist(['tl_style_manager_archive', 'tl_style_manager']);
    }

    /**
     * Whether at least one Creuva archive is in the database.
     */
    public function hasArchives(): bool
    {
        return $this->isAvailable() && \count($this->getMissingArchives()) < \count($this->load());
    }

    /**
     * Identifiers of the Creuva archives that are not in the database yet.
     *
     * @return list<string>
     */
    public function getMissingArchives(): array
    {
        if (!$this->isAvailable()) {
            return [];
        }

        $existing = $this->connection->fetchFirstColumn('SELECT identifier FROM tl_style_manager_archive');

        return array_values(array_diff(array_keys($this->load()), $existing));
    }

    /**
     * @return array{archives: int, groups: int, classes: int} number of added records/classes
     */
    public function import(): array
    {
        $count = ['archives' => 0, 'groups' => 0, 'classes' => 0];

        if (!$this->isAvailable()) {
            return $count;
        }

        $this->connection->transactional(function () use (&$count): void {
            foreach ($this->load() as $identifier => $archive) {
                $archiveId = $this->connection->fetchOne('SELECT id FROM tl_style_manager_archive WHERE identifier = ?', [$identifier]);

                if (false === $archiveId) {
                    $this->connection->insert('tl_style_manager_archive', [
                        'tstamp' => time(),
                        'title' => (string) ($archive['title'] ?? $identifier),
                        $this->connection->quoteIdentifier('desc') => (string) ($archive['desc'] ?? ''), // reserved word
                        'identifier' => $identifier,
                        'groupAlias' => (string) ($archive['groupAlias'] ?? ''),
                        'sorting' => (int) ($archive['sorting'] ?? 0),
                    ]);

                    $archiveId = $this->connection->lastInsertId();
                    ++$count['archives'];
                }

                foreach ($archive['children'] ?? [] as $alias => $group) {
                    $this->importGroup((int) $archiveId, (string) $alias, $group, $count);
                }
            }
        });

        return $count;
    }

    /**
     * @param array<string, mixed>                          $group
     * @param array{archives: int, groups: int, classes: int} $count
     */
    private function importGroup(int $archiveId, string $alias, array $group, array &$count): void
    {
        $classes = [];

        foreach ($group['cssClasses'] ?? [] as $class => $label) {
            $classes[] = ['key' => (string) $class, 'value' => (string) $label];
        }

        $existing = $this->connection->fetchAssociative('SELECT * FROM tl_style_manager WHERE pid = ? AND alias = ?', [$archiveId, $alias]);

        // Existing group: only add new classes and new element types (content
        // elements, modules, form fields), never remove or change anything
        if (false !== $existing) {
            $update = [];
            $current = @unserialize((string) $existing['cssClasses'], ['allowed_classes' => false]);
            $current = \is_array($current) ? $current : [];
            $keys = array_column($current, 'key');
            $added = array_values(array_filter($classes, static fn (array $class): bool => !\in_array($class['key'], $keys, true)));

            if ($added) {
                $update['cssClasses'] = serialize([...$current, ...$added]);
                $count['classes'] += \count($added);
            }

            foreach (self::SERIALIZED as $field) {
                if (!isset($group[$field]) || !\array_key_exists($field, $existing)) {
                    continue;
                }

                $types = @unserialize((string) $existing[$field], ['allowed_classes' => false]);
                $types = \is_array($types) ? $types : [];
                $new = array_values(array_diff((array) $group[$field], $types));

                if ($new) {
                    $update[$field] = serialize([...$types, ...$new]);
                }
            }

            if ($update) {
                $this->connection->update('tl_style_manager', [...$update, 'tstamp' => time()], ['id' => $existing['id']]);
            }

            return;
        }

        $data = [
            'pid' => $archiveId,
            'tstamp' => time(),
            'alias' => $alias,
            'cssClasses' => serialize($classes),
        ];

        foreach ($group as $field => $value) {
            if (\in_array($field, ['cssClasses', 'children'], true)) {
                continue;
            }

            $data[$field] = match (true) {
                \in_array($field, self::SERIALIZED, true) => serialize(array_values((array) $value)),
                \is_bool($value) => $value ? '1' : '',
                default => (string) $value,
            };
        }

        $this->connection->insert('tl_style_manager', $data);
        ++$count['groups'];
    }

    /**
     * @return array<string, array<string, mixed>>
     */
    private function load(): array
    {
        return Yaml::parseFile(StyleManagerValues::CONFIG) ?? [];
    }
}
