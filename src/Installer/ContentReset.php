<?php

declare(strict_types=1);

/*
 * Creuva theme bundle for Contao Open Source CMS
 *
 * Copyright (C) pdir GmbH <develop@pdir.de>
 */

namespace Pdir\CreuvaThemeBundle\Installer;

use Contao\CoreBundle\Filesystem\VirtualFilesystemInterface;
use Doctrine\DBAL\Connection;
use Symfony\Component\DependencyInjection\Attribute\Autowire;

/**
 * Resets the contents of a Contao installation to the state after a fresh
 * installation: all Contao tables (tl_*) are emptied, except the back end
 * users, members, the file system table and technical tables. The demo files
 * folder is removed, other files are kept.
 */
class ContentReset
{
    /**
     * Tables that are kept (accounts, file system, sessions and queues).
     */
    public const KEEP_TABLES = [
        'tl_user',
        'tl_user_group',
        'tl_member',
        'tl_member_group',
        'tl_files',
        'tl_favorites',
        'tl_remember_me',
        'tl_trusted_device',
        'tl_webauthn_credential',
        'tl_opt_in',
        'tl_opt_in_related',
        'tl_cron_job',
        'tl_message_queue',
    ];

    public function __construct(
        private readonly Connection $connection,
        #[Autowire(service: 'contao.filesystem.virtual.files')]
        private readonly VirtualFilesystemInterface $filesStorage,
    ) {
    }

    /**
     * Returns the tables that would be emptied, with their number of rows.
     *
     * @return array<string, int> table => rows
     */
    public function getTables(): array
    {
        $tables = [];

        foreach ($this->connection->createSchemaManager()->listTableNames() as $table) {
            if (str_starts_with($table, 'tl_') && !\in_array($table, self::KEEP_TABLES, true)) {
                $tables[$table] = (int) $this->connection->fetchOne('SELECT COUNT(*) FROM '.$this->connection->quoteIdentifier($table));
            }
        }

        ksort($tables);

        return $tables;
    }

    public function hasDemoFiles(): bool
    {
        return $this->filesStorage->directoryExists(DemoImporter::FILES_FOLDER);
    }

    /**
     * Empties the tables (IDs start at 1 again) and removes the demo files.
     *
     * @return list<string> emptied tables
     */
    public function reset(): array
    {
        $tables = array_keys($this->getTables());

        // TRUNCATE cannot run in a transaction (implicit commit in MySQL)
        foreach ($tables as $table) {
            $this->connection->executeStatement('TRUNCATE TABLE '.$this->connection->quoteIdentifier($table));
        }

        // Removes the folder and its entries in tl_files
        if ($this->hasDemoFiles()) {
            $this->filesStorage->deleteDirectory(DemoImporter::FILES_FOLDER);
        }

        return $tables;
    }
}
