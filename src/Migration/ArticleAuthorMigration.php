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
 * Articles and news of older demo imports have no author, which is a mandatory
 * field in the back end: they get the first administrator (otherwise the first
 * back end user).
 */
class ArticleAuthorMigration extends AbstractMigration
{
    private const TABLES = ['tl_article', 'tl_news'];

    public function __construct(private readonly Connection $connection)
    {
    }

    public function shouldRun(): bool
    {
        if (null === $this->getAuthor()) {
            return false;
        }

        foreach ($this->getTables() as $table) {
            if ($this->connection->fetchOne("SELECT COUNT(*) FROM $table WHERE author = 0")) {
                return true;
            }
        }

        return false;
    }

    public function run(): MigrationResult
    {
        $count = 0;

        foreach ($this->getTables() as $table) {
            $count += (int) $this->connection->executeStatement("UPDATE $table SET author = ? WHERE author = 0", [$this->getAuthor()]);
        }

        return $this->createResult(true, \sprintf('Set the author of %d articles and news.', $count));
    }

    private function getAuthor(): int|null
    {
        $schemaManager = $this->connection->createSchemaManager();

        if (!$schemaManager->tablesExist(['tl_user'])) {
            return null;
        }

        $id = $this->connection->fetchOne('SELECT id FROM tl_user ORDER BY admin DESC, id LIMIT 1');

        return false === $id ? null : (int) $id;
    }

    /**
     * @return list<string>
     */
    private function getTables(): array
    {
        $schemaManager = $this->connection->createSchemaManager();

        return array_values(array_filter(
            self::TABLES,
            static fn (string $table): bool => $schemaManager->tablesExist([$table]) && \array_key_exists('author', array_change_key_case($schemaManager->listTableColumns($table))),
        ));
    }
}
