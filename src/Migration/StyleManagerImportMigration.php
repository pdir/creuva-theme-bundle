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
use Pdir\CreuvaThemeBundle\Installer\StyleManagerImporter;

/**
 * Imports the Creuva groups into the Style Manager once (e.g. after an update
 * from a version that loaded them as bundle configuration). It does not run
 * again as long as at least one Creuva archive exists, so archives removed by
 * an editor stay removed; new groups and classes of a theme update are added
 * with "creuva:style-manager".
 */
class StyleManagerImportMigration extends AbstractMigration
{
    public function __construct(private readonly StyleManagerImporter $importer)
    {
    }

    public function shouldRun(): bool
    {
        return $this->importer->isAvailable() && !$this->importer->hasArchives();
    }

    public function run(): MigrationResult
    {
        $count = $this->importer->import();

        return $this->createResult(true, \sprintf('Imported %d Creuva archives with %d groups into the Style Manager.', $count['archives'], $count['groups']));
    }
}
