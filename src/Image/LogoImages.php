<?php

declare(strict_types=1);

/*
 * Creuva theme bundle for Contao Open Source CMS
 *
 * Copyright (C) pdir GmbH <develop@pdir.de>
 */

namespace Pdir\CreuvaThemeBundle\Image;

use Contao\CoreBundle\Framework\ContaoFramework;
use Contao\CoreBundle\Image\Studio\Figure;
use Contao\CoreBundle\Image\Studio\Studio;
use Contao\FilesModel;
use Contao\StringUtil;

/**
 * Logos of the logo bar (content element and front end module creuva_logos):
 * the images of the selected files and folders in the order of the selection,
 * each linked to the URL of its file metadata.
 */
class LogoImages
{
    public function __construct(
        private readonly ContaoFramework $framework,
        private readonly Studio $studio,
    ) {
    }

    /**
     * @return list<array{figure: Figure, href: string|null}>
     */
    public function build(mixed $multiSRC, mixed $size): array
    {
        $logos = [];

        foreach ($this->getImages($multiSRC) as $file) {
            $figure = $this->studio
                ->createFigureBuilder()
                ->fromFilesModel($file)
                ->setSize($size)
                ->buildIfResourceExists()
            ;

            if (null !== $figure) {
                $logos[] = ['figure' => $figure, 'href' => $figure->hasMetadata() ? ($figure->getMetadata()->getUrl() ?: null) : null];
            }
        }

        return $logos;
    }

    /**
     * @return list<FilesModel>
     */
    private function getImages(mixed $multiSRC): array
    {
        $uuids = StringUtil::deserialize($multiSRC, true);
        $adapter = $this->framework->getAdapter(FilesModel::class);

        if (!$uuids || !$files = $adapter->findMultipleByUuids($uuids)) {
            return [];
        }

        $images = [];

        foreach ($files as $file) {
            if ('folder' === $file->type) {
                foreach ($adapter->findByPid($file->uuid, ['order' => 'name']) ?? [] as $child) {
                    if ('file' === $child->type) {
                        $images[] = $child;
                    }
                }

                continue;
            }

            $images[] = $file;
        }

        // Keep the order of the selection (findMultipleByUuids sorts by path)
        $position = array_flip(array_map(static fn ($uuid): string => (string) $uuid, $uuids));
        usort($images, static fn (FilesModel $a, FilesModel $b): int => ($position[$a->uuid] ?? $position[$a->pid] ?? 0) <=> ($position[$b->uuid] ?? $position[$b->pid] ?? 0));

        return $images;
    }
}
