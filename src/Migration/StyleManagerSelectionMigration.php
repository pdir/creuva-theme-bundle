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
use Pdir\CreuvaThemeBundle\Installer\StyleManagerValues;

/**
 * Creuva classes entered in the CSS class field (e.g. by an older demo import)
 * become a selection in the Style Manager, so editors see and change them
 * there. Only records without a Style Manager selection are updated; the CSS
 * classes themselves remain unchanged.
 */
class StyleManagerSelectionMigration extends AbstractMigration
{
    private const TABLES = ['tl_content', 'tl_article', 'tl_module'];

    public function __construct(
        private readonly Connection $connection,
        private readonly StyleManagerValues $styleManagerValues,
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
            $this->connection->update($table, ['styleManager' => $value], ['id' => $id]);
        }

        return $this->createResult(true, \sprintf('Preselected the Creuva classes of %d records in the Style Manager.', \count($updates)));
    }

    /**
     * @return list<array{string, int, string}> table, ID, styleManager value
     */
    private function getUpdates(int|null $limit = null): array
    {
        $schemaManager = $this->connection->createSchemaManager();
        $updates = [];

        foreach (self::TABLES as $table) {
            if (!$schemaManager->tablesExist([$table])) {
                continue;
            }

            $columns = array_map(strtolower(...), array_keys($schemaManager->listTableColumns($table)));

            if (!\in_array('stylemanager', $columns, true) || !\in_array('cssid', $columns, true)) {
                continue;
            }

            $type = \in_array('type', $columns, true) ? 'type' : 'NULL AS type';

            $rows = $this->connection->fetchAllAssociative(
                "SELECT id, $type, cssID FROM $table WHERE cssID LIKE '%u-%' AND (styleManager IS NULL OR styleManager = '' OR styleManager = 'a:0:{}')",
            );

            foreach ($rows as $row) {
                $cssID = @unserialize((string) $row['cssID'], ['allowed_classes' => false]);

                if (!\is_array($cssID) || null === ($value = $this->styleManagerValues->resolve($table, 'tl_content' === $table ? (string) $row['type'] : null, (string) ($cssID[1] ?? '')))) {
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
