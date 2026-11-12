<?php

declare(strict_types=1);

/*
 * Creuva theme bundle for Contao Open Source CMS
 *
 * Copyright (C) pdir GmbH <develop@pdir.dev>
 */

namespace Pdir\CreuvaThemeBundle\Design;

use Contao\CoreBundle\Framework\ContaoFramework;
use Contao\FilesModel;
use Contao\StringUtil;
use Symfony\Component\DependencyInjection\Attribute\Autowire;

/**
 * Paths of the own stylesheets of a page layout (field "creuvaStylesheets") in
 * the selected order, with the modification time for cache busting.
 */
class StylesheetResolver
{
    public function __construct(
        private readonly ContaoFramework $framework,
        #[Autowire(param: 'kernel.project_dir')]
        private readonly string $projectDir,
    ) {
    }

    /**
     * @param array<string, mixed>|null $layout
     *
     * @return list<string> e.g. "files/design/custom.css?v=1760600000"
     */
    public function resolve(array|null $layout): array
    {
        $uuids = StringUtil::deserialize($layout['creuvaStylesheets'] ?? null, true);

        if (!$uuids) {
            return [];
        }

        $this->framework->initialize();

        $stylesheets = [];

        foreach ($uuids as $uuid) {
            $file = $this->framework->getAdapter(FilesModel::class)->findByUuid($uuid);

            if (null === $file || 'file' !== $file->type || 'css' !== strtolower((string) $file->extension)) {
                continue;
            }

            $path = $this->projectDir.'/'.$file->path;

            if (is_file($path)) {
                $stylesheets[] = $file->path.'?v='.filemtime($path);
            }
        }

        return $stylesheets;
    }
}
