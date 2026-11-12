<?php

declare(strict_types=1);

/*
 * Creuva theme bundle for Contao Open Source CMS
 *
 * Copyright (C) pdir GmbH <develop@pdir.dev>
 */

namespace Pdir\CreuvaThemeBundle\Image;

use Contao\CoreBundle\Image\Studio\Studio;
use Contao\Image\PictureConfiguration;
use Contao\Image\PictureConfigurationItem;
use Contao\Image\ResizeConfiguration;
use Contao\StringUtil;
use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Exception as DbalException;
use Pdir\CreuvaThemeBundle\OpenGraph\OpenGraphArticle;

/**
 * Image for the link preview of a page (Open Graph), in this order:
 *
 *  1. the image of the news shown by a news reader
 *  2. the first hero, image or element with an image in the main column,
 *     also inside element groups and other nested elements
 *  3. the preview image of the website root (creuvaOgImage)
 *
 * SVG files are skipped, social networks do not show them. WebP, AVIF and GIF
 * files are converted to PNG, not every network shows them.
 */
class OpenGraphImage
{
    public const WIDTH = 1200;

    public const HEIGHT = 630;

    private const RASTER_EXTENSIONS = ['jpg', 'jpeg', 'png', 'webp', 'avif', 'gif'];

    public function __construct(
        private readonly Connection $connection,
        private readonly Studio $studio,
        private readonly OpenGraphArticle $article,
    ) {
    }

    /**
     * @param array<string, mixed> $page page data of the page layout
     *
     * @return array{src: string, width: int, height: int, alt: string}|null
     */
    public function resolve(array $page): array|null
    {
        $alt = null;

        if (null !== ($extension = $this->rasterExtension($uuid = $this->article->getImageUuid()))) {
            $alt = $this->article->getImageAlt();
        } elseif (null === ($extension = $this->rasterExtension($uuid = $this->findInContent((int) ($page['id'] ?? 0))))) {
            if (null === ($extension = $this->rasterExtension($uuid = $this->rootImage((int) ($page['rootId'] ?? 0))))) {
                return null;
            }
        }

        // 1200 × 630 px, JPEG stays JPEG, everything else becomes PNG
        $size = (new PictureConfiguration())
            ->setSize((new PictureConfigurationItem())->setResizeConfig(
                (new ResizeConfiguration())->setWidth(self::WIDTH)->setHeight(self::HEIGHT)->setMode(ResizeConfiguration::MODE_CROP),
            ))
            ->setFormats([$extension => [\in_array($extension, ['jpg', 'jpeg'], true) ? $extension : 'png']])
        ;

        $figure = $this->studio
            ->createFigureBuilder()
            ->fromUuid($uuid)
            ->setSize($size)
            ->buildIfResourceExists()
        ;

        if (null === $figure) {
            return null;
        }

        $img = $figure->getImage()->getImg();

        return [
            'src' => $figure->getImage()->getImageSrc(),
            'width' => (int) ($img['width'] ?? self::WIDTH),
            'height' => (int) ($img['height'] ?? self::HEIGHT),
            'alt' => $alt ?? ($figure->hasMetadata() ? (string) $figure->getMetadata()->getAlt() : ''),
        ];
    }

    /**
     * First visible element with a raster image in the main column, in the
     * order of the articles and elements (nested elements at the position of
     * their parent element).
     */
    private function findInContent(int $pageId): string|null
    {
        $now = time();
        $extensions = implode(', ', array_map(fn (string $extension) => $this->connection->quote($extension), self::RASTER_EXTENSIONS));

        try {
            $uuid = $this->connection->fetchOne(
                <<<SQL
                    WITH RECURSIVE elements AS (
                        SELECT c.id, c.type, c.singleSRC, c.addImage,
                            CAST(CONCAT(LPAD(a.sorting, 10, '0'), '.', LPAD(c.sorting, 10, '0')) AS CHAR(1000)) AS position
                        FROM tl_content c
                        INNER JOIN tl_article a ON a.id = c.pid AND c.ptable = 'tl_article'
                        WHERE a.pid = :page
                            AND a.inColumn = 'main'
                            AND a.published = '1'
                            AND (a.start = '' OR a.start <= :now)
                            AND (a.stop = '' OR a.stop > :now)
                            AND c.invisible != '1'
                            AND (c.start = '' OR c.start <= :now)
                            AND (c.stop = '' OR c.stop > :now)
                        UNION ALL
                        SELECT c.id, c.type, c.singleSRC, c.addImage,
                            CONCAT(e.position, '.', LPAD(c.sorting, 10, '0'))
                        FROM tl_content c
                        INNER JOIN elements e ON e.id = c.pid AND c.ptable = 'tl_content'
                        WHERE c.invisible != '1'
                            AND (c.start = '' OR c.start <= :now)
                            AND (c.stop = '' OR c.stop > :now)
                    )
                    SELECT e.singleSRC
                    FROM elements e
                    INNER JOIN tl_files f ON f.uuid = e.singleSRC
                    WHERE (e.type IN ('creuva_hero', 'image') OR e.addImage = '1')
                        AND f.type = 'file'
                        AND LOWER(f.extension) IN ($extensions)
                    ORDER BY e.position
                    LIMIT 1
                    SQL,
                ['page' => $pageId, 'now' => $now],
            );
        } catch (DbalException) {
            // The database has not been migrated yet
            return null;
        }

        return \is_string($uuid) && '' !== $uuid ? StringUtil::binToUuid($uuid) : null;
    }

    private function rootImage(int $rootId): string|null
    {
        try {
            $uuid = $this->connection->fetchOne('SELECT creuvaOgImage FROM tl_page WHERE id = ?', [$rootId]);
        } catch (DbalException) {
            return null;
        }

        return \is_string($uuid) && 16 === \strlen($uuid) ? StringUtil::binToUuid($uuid) : null;
    }

    /**
     * Returns the file extension if the UUID belongs to a raster image in the
     * file manager.
     */
    private function rasterExtension(string|null $uuid): string|null
    {
        if (null === $uuid) {
            return null;
        }

        $extension = $this->connection->fetchOne("SELECT LOWER(extension) FROM tl_files WHERE uuid = ? AND type = 'file'", [StringUtil::uuidToBin($uuid)]);

        return \in_array($extension, self::RASTER_EXTENSIONS, true) ? $extension : null;
    }
}
