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

/**
 * Creuva no longer provides a class set for the advanced classes bundle (the
 * classes are offered as CSS styles). Website roots and forms that still use
 * the removed set are reset, otherwise the bundle would try to load the
 * missing file.
 */
class RemoveClassSetMigration extends AbstractMigration
{
    private const CLASS_SET = 'bundles/creuvatheme/sets/creuva.json';

    private const TABLES = ['tl_page', 'tl_form'];

    public function __construct(private readonly Connection $connection)
    {
    }

    public function shouldRun(): bool
    {
        foreach ($this->getTables() as $table) {
            if ($this->connection->fetchOne("SELECT COUNT(*) FROM $table WHERE ac_set = ?", [self::CLASS_SET])) {
                return true;
            }
        }

        return false;
    }

    public function run(): MigrationResult
    {
        $count = 0;

        foreach ($this->getTables() as $table) {
            $count += $this->connection->executeStatement("UPDATE $table SET ac_set = '' WHERE ac_set = ?", [self::CLASS_SET]);
        }

        return $this->createResult(true, \sprintf('Removed the Creuva class set of the advanced classes bundle from %d records.', $count));
    }

    /**
     * @return list<string> tables with the column of the advanced classes bundle
     */
    private function getTables(): array
    {
        $schemaManager = $this->connection->createSchemaManager();

        return array_values(array_filter(
            self::TABLES,
            static fn (string $table): bool => $schemaManager->tablesExist([$table]) && isset(array_change_key_case($schemaManager->listTableColumns($table))['ac_set']),
        ));
    }
}
