<?php

declare(strict_types=1);

/*
 * Creuva theme bundle for Contao Open Source CMS
 *
 * Copyright (C) pdir GmbH <develop@pdir.de>
 */

namespace Pdir\CreuvaThemeBundle\Design;

use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Exception as DbalException;

/**
 * Resolves the design settings of a page from its page layout and its website
 * root (see DesignSettings::resolve()).
 */
class DesignResolver
{
    /**
     * @var array<int, array<string, mixed>|null>
     */
    private array $rootPages = [];

    public function __construct(
        private readonly DesignSettings $designSettings,
        private readonly Connection $connection,
    ) {
    }

    /**
     * @param array<string, mixed>      $page   page with details (rootId)
     * @param array<string, mixed>|null $layout
     *
     * @return array{variant: string, typography: string, scheme: string, radius: string, primary: string, accent: string, logoHeight: string}
     */
    public function resolve(array $page, array|null $layout): array
    {
        return $this->designSettings->resolve($layout, $this->getRootPage((int) ($page['rootId'] ?? 0)));
    }

    /**
     * @return array<string, mixed>|null
     */
    private function getRootPage(int $rootId): array|null
    {
        if ($rootId < 1) {
            return null;
        }

        if (!\array_key_exists($rootId, $this->rootPages)) {
            try {
                $this->rootPages[$rootId] = $this->connection->fetchAssociative(
                    'SELECT creuvaScheme, creuvaVariant, creuvaTypography, creuvaRadius FROM tl_page WHERE id = ?',
                    [$rootId],
                ) ?: null;
            } catch (DbalException) {
                // The database has not been migrated yet
                $this->rootPages[$rootId] = null;
            }
        }

        return $this->rootPages[$rootId];
    }
}
