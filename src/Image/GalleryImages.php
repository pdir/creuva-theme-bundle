<?php

declare(strict_types=1);

/*
 * Creuva theme bundle for Contao Open Source CMS
 *
 * Copyright (C) pdir GmbH <develop@pdir.de>
 */

namespace Pdir\CreuvaThemeBundle\Image;

use Contao\CoreBundle\Image\Studio\Figure;
use Contao\FilesModel;

/**
 * Helpers for the images of the gallery element (template
 * content_element/gallery.html.twig):
 *
 *  - sizes(): the "sizes" attribute of an image depending on the gallery layout
 *    (CSS classes u-gallery-*), its position and the images per row, so the
 *    browser loads the width that fits the cell (image size with several
 *    widths, e.g. "Galerie (responsiv)"). An image that is wider than its cell
 *    is cropped by object-fit and shown larger than the cell, the "sizes" value
 *    takes this into account, otherwise the image would be enlarged and blurry.
 *  - focus(): the important part of the image (file manager) as CSS
 *    object-position, so cropping by object-fit keeps the subject
 *
 * The values follow the grid of assets/scss/components/_content.scss: content
 * width at most 76rem (1216px, reached from an 80rem window), two or more
 * columns from 48rem.
 */
class GalleryImages
{
    private const CONTAINER = 1216;

    // Width ÷ height of the cells on wide screens (see _content.scss)
    private const RATIO_REGULAR = 4 / 3;

    private const RATIO_MOSAIC_LARGE = 600 / 528;

    private const RATIO_MOSAIC_WIDE = 600 / 256;

    private const RATIO_MOSAIC_SMALL = 292 / 256;

    private const RATIO_FEATURED_LARGE = 1216 / 464;

    private const RATIO_FEATURED_SMALL = 400 / 224;

    /**
     * @var array<string, string|null> file path => object-position
     */
    private array $focus = [];

    /**
     * @param string $classes  CSS classes of the gallery element
     * @param int    $index    position of the image (0-based)
     * @param int    $perRow   images per row (gallery setting)
     * @param mixed  $figure   figure of the image, for its proportions
     */
    public function sizes(string $classes, int $index, int $perRow = 4, mixed $figure = null): string
    {
        $perRow = max(1, min(6, $perRow));

        // Mosaic: large (2 × 2) and wide image take half, the two small ones a quarter
        // of the width; on small screens the large and the last one span both columns
        if (preg_match('/\bu-gallery-mosaic(-right)?\b/', $classes)) {
            return match ($index % 4) {
                0 => $this->fit($this->build(2, '100vw'), $figure, self::RATIO_MOSAIC_LARGE),
                1 => $this->fit($this->build(2, '50vw'), $figure, self::RATIO_MOSAIC_WIDE),
                2 => $this->fit($this->build(4, '50vw'), $figure, self::RATIO_MOSAIC_SMALL),
                default => $this->fit($this->build(4, '100vw'), $figure, self::RATIO_MOSAIC_SMALL),
            };
        }

        // Featured: the first image over the full width, the others in three columns
        if (preg_match('/\bu-gallery-featured\b/', $classes)) {
            return 0 === $index
                ? $this->fit($this->build(1, '100vw'), $figure, self::RATIO_FEATURED_LARGE)
                : $this->fit($this->build(3, '50vw'), $figure, self::RATIO_FEATURED_SMALL);
        }

        // Masonry: up to three columns of at least 14rem
        if (preg_match('/\bu-gallery-masonry\b/', $classes)) {
            return $this->build(3, '(min-width: 30rem) 50vw, 100vw');
        }

        // Regular grid: images per row from 48rem, below up to two columns of 14rem
        return $this->fit($this->build($perRow, 1 === $perRow ? '100vw' : '(min-width: 30rem) 50vw, 100vw'), $figure, self::RATIO_REGULAR);
    }

    /**
     * Important part of the image (center) as CSS object-position, null if none
     * is set.
     */
    public function focus(mixed $figure): string|null
    {
        if (!$figure instanceof Figure) {
            return null;
        }

        $path = $figure->getImage()->getFilePath();

        if (\array_key_exists($path, $this->focus)) {
            return $this->focus[$path];
        }

        $file = FilesModel::findByPath($path);
        $width = (float) ($file?->importantPartWidth ?? 0);
        $height = (float) ($file?->importantPartHeight ?? 0);

        // No important part or the whole image
        if ($width <= 0 || $height <= 0 || ($width >= 1 && $height >= 1)) {
            return $this->focus[$path] = null;
        }

        $x = ((float) $file->importantPartX + $width / 2) * 100;
        $y = ((float) $file->importantPartY + $height / 2) * 100;

        return $this->focus[$path] = \sprintf('%s%% %s%%', round($x, 1), round($y, 1));
    }

    /**
     * Enlarges the widths of "sizes" if the image is wider than its cell: it fills
     * the height of the cell and its sides are cropped.
     */
    private function fit(string $sizes, mixed $figure, float $cellRatio): string
    {
        if (!$figure instanceof Figure) {
            return $sizes;
        }

        $size = $figure->getImage()->getOriginalDimensions()->getSize();

        if ($size->getWidth() <= 0 || $size->getHeight() <= 0) {
            return $sizes;
        }

        $scale = $size->getWidth() / $size->getHeight() / $cellRatio;

        if ($scale <= 1) {
            return $sizes;
        }

        return (string) preg_replace_callback(
            '/(\d+(?:\.\d+)?)(px|vw)/',
            static fn (array $m): string => round((float) $m[1] * $scale, 2).$m[2],
            $sizes,
        );
    }

    /**
     * @param int    $columns share of the content width from 48rem
     * @param string $mobile  sizes below 48rem
     */
    private function build(int $columns, string $mobile): string
    {
        $desktop = \sprintf('(min-width: 80rem) %dpx, (min-width: 48rem) %svw', round(self::CONTAINER / $columns), round(100 / $columns, 2));

        return $desktop.', '.$mobile;
    }
}
