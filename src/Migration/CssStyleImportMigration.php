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
use Pdir\CreuvaThemeBundle\Installer\CssStyleImporter;

/**
 * Imports the Creuva groups into the CSS style selector once (e.g. after an
 * update from a version that used the Style Manager). It does not run again as
 * long as at least one Creuva group exists, so groups removed by an editor stay
 * removed; new groups and styles of a theme update are added with
 * "creuva:css-styles".
 */
class CssStyleImportMigration extends AbstractMigration
{
    public function __construct(private readonly CssStyleImporter $importer)
    {
    }

    public function shouldRun(): bool
    {
        return $this->importer->isAvailable() && !$this->importer->hasGroups();
    }

    public function run(): MigrationResult
    {
        $count = $this->importer->import();

        return $this->createResult(true, \sprintf('Imported %d Creuva groups with %d styles into the CSS style selector.', $count['groups'], $count['styles']));
    }
}
