<?php

declare(strict_types=1);

/*
 * Creuva theme bundle for Contao Open Source CMS
 *
 * Copyright (C) pdir GmbH <develop@pdir.de>
 */

namespace Pdir\CreuvaThemeBundle\Image;

use Contao\StringUtil;
use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Exception as DbalException;

/**
 * Image for the link preview of a page (Open Graph): the first hero or image
 * element in the main column. SVG files are skipped, social networks do not
 * show them.
 */
class OpenGraphImage
{
    public function __construct(private readonly Connection $connection)
    {
    }

    /**
     * @return string|null UUID of the file for figure()
     */
    public function resolve(int $pageId): string|null
    {
        $now = time();

        try {
            $uuid = $this->connection->fetchOne(
                <<<'SQL'
                    SELECT c.singleSRC
                    FROM tl_content c
                    INNER JOIN tl_article a ON a.id = c.pid AND c.ptable = 'tl_article'
                    INNER JOIN tl_files f ON f.uuid = c.singleSRC
                    WHERE a.pid = ?
                        AND a.inColumn = 'main'
                        AND a.published = '1'
                        AND (a.start = '' OR a.start <= ?)
                        AND (a.stop = '' OR a.stop > ?)
                        AND c.type IN ('creuva_hero', 'image')
                        AND c.invisible != '1'
                        AND (c.start = '' OR c.start <= ?)
                        AND (c.stop = '' OR c.stop > ?)
                        AND f.type = 'file'
                        AND LOWER(f.extension) != 'svg'
                    ORDER BY a.sorting, c.sorting
                    SQL,
                [$pageId, $now, $now, $now, $now],
            );
        } catch (DbalException) {
            // The database has not been migrated yet
            return null;
        }

        return \is_string($uuid) && '' !== $uuid ? StringUtil::binToUuid($uuid) : null;
    }
}
