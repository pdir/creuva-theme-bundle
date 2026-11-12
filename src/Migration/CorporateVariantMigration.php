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
 * The former design variant "corporate" is the color scheme "blue" now.
 */
class CorporateVariantMigration extends AbstractMigration
{
    public function __construct(private readonly Connection $connection)
    {
    }

    public function shouldRun(): bool
    {
        $schemaManager = $this->connection->createSchemaManager();

        if (!$schemaManager->tablesExist(['tl_layout'])) {
            return false;
        }

        $columns = array_map(strtolower(...), array_keys($schemaManager->listTableColumns('tl_layout')));

        if (!\in_array('creuvavariant', $columns, true) || !\in_array('creuvascheme', $columns, true)) {
            return false;
        }

        return (bool) $this->connection->fetchOne("SELECT COUNT(*) FROM tl_layout WHERE creuvaVariant = 'corporate'");
    }

    public function run(): MigrationResult
    {
        $this->connection->executeStatement("UPDATE tl_layout SET creuvaVariant = 'light', creuvaScheme = 'blue' WHERE creuvaVariant = 'corporate'");

        return $this->createResult(true, 'Migrated the Creuva variant "corporate" to the color scheme "blue".');
    }
}
