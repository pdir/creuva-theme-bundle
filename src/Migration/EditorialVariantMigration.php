<?php

declare(strict_types=1);

/*
 * Creuva theme bundle for Contao Open Source CMS
 *
 * Copyright (C) pdir GmbH <develop@pdir.dev>
 */

namespace Pdir\CreuvaThemeBundle\Migration;

use Contao\CoreBundle\Migration\AbstractMigration;
use Contao\CoreBundle\Migration\MigrationResult;
use Doctrine\DBAL\Connection;

/**
 * The former design variant "editorial" is the typography "editorial" now, so
 * it can be combined with every color mode (light, dark, auto).
 */
class EditorialVariantMigration extends AbstractMigration
{
    private const TABLES = ['tl_layout', 'tl_page'];

    public function __construct(private readonly Connection $connection)
    {
    }

    public function shouldRun(): bool
    {
        foreach ($this->getTables() as $table) {
            if ($this->connection->fetchOne("SELECT COUNT(*) FROM $table WHERE creuvaVariant = 'editorial'")) {
                return true;
            }
        }

        return false;
    }

    public function run(): MigrationResult
    {
        foreach ($this->getTables() as $table) {
            $this->connection->executeStatement("UPDATE $table SET creuvaVariant = 'light', creuvaTypography = 'editorial' WHERE creuvaVariant = 'editorial'");
        }

        return $this->createResult(true, 'Migrated the Creuva variant "editorial" to the typography "editorial".');
    }

    /**
     * Tables that already have both columns (the typography column is added by
     * the database update, the migration runs again afterwards).
     *
     * @return list<string>
     */
    private function getTables(): array
    {
        $schemaManager = $this->connection->createSchemaManager();
        $tables = [];

        foreach (self::TABLES as $table) {
            if (!$schemaManager->tablesExist([$table])) {
                continue;
            }

            $columns = array_map(strtolower(...), array_keys($schemaManager->listTableColumns($table)));

            if (\in_array('creuvavariant', $columns, true) && \in_array('creuvatypography', $columns, true)) {
                $tables[] = $table;
            }
        }

        return $tables;
    }
}
