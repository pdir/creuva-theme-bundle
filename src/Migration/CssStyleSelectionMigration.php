<?php

declare(strict_types=1);

/*
 * Creuva theme bundle for Contao Open Source CMS
 *
 * Copyright (C) pdir GmbH <develop@pdir.de>
 */

namespace Pdir\CreuvaThemeBundle\Migration;

use Contao\CoreBundle\Migration\AbstractMigration;
use Contao\CoreBundle\Migration\MigrationResult;
use Doctrine\DBAL\Connection;
use Pdir\CreuvaThemeBundle\Installer\CssStyleValues;

/**
 * Creuva classes in the CSS class field (e.g. from an older demo import or a
 * selection in the Style Manager) become a selection in the CSS style
 * selector, so editors see and change them there. Only records without a
 * selected style are updated; the CSS classes themselves remain unchanged.
 */
class CssStyleSelectionMigration extends AbstractMigration
{
    private const TABLES = ['tl_content', 'tl_article', 'tl_module'];

    public function __construct(
        private readonly Connection $connection,
        private readonly CssStyleValues $cssStyleValues,
    ) {
    }

    public function shouldRun(): bool
    {
        return [] !== $this->getUpdates(1);
    }

    public function run(): MigrationResult
    {
        $updates = $this->getUpdates();

        foreach ($updates as [$table, $id, $value]) {
            $this->connection->update($table, [CssStyleValues::FIELD => $value], ['id' => $id]);
        }

        return $this->createResult(true, \sprintf('Preselected the Creuva classes of %d records in the CSS style selector.', \count($updates)));
    }

    /**
     * @return list<array{string, int, string}> table, ID, cssStyleSelector value
     */
    private function getUpdates(int|null $limit = null): array
    {
        $schemaManager = $this->connection->createSchemaManager();

        if (!$schemaManager->tablesExist(['tl_css_style_group']) || !isset(array_change_key_case($schemaManager->listTableColumns('tl_css_style_group'))['styles'])) {
            return [];
        }

        $updates = [];

        foreach (self::TABLES as $table) {
            if (!$schemaManager->tablesExist([$table])) {
                continue;
            }

            $columns = array_map(strtolower(...), array_keys($schemaManager->listTableColumns($table)));

            if (!\in_array(strtolower(CssStyleValues::FIELD), $columns, true) || !\in_array('cssid', $columns, true)) {
                continue;
            }

            $type = \in_array('type', $columns, true) ? 'type' : 'NULL AS type';

            $rows = $this->connection->fetchAllAssociative(
                "SELECT id, $type, cssID FROM $table WHERE cssID LIKE '%u-%' AND (cssStyleSelector IS NULL OR cssStyleSelector = '' OR cssStyleSelector = 'a:0:{}')",
            );

            foreach ($rows as $row) {
                $cssID = @unserialize((string) $row['cssID'], ['allowed_classes' => false]);

                if (!\is_array($cssID) || null === ($value = $this->cssStyleValues->resolve($table, null === $row['type'] ? null : (string) $row['type'], (string) ($cssID[1] ?? '')))) {
                    continue;
                }

                $updates[] = [$table, (int) $row['id'], $value];

                if (null !== $limit && \count($updates) >= $limit) {
                    return $updates;
                }
            }
        }

        return $updates;
    }
}
