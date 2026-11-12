<?php

declare(strict_types=1);

/*
 * Creuva theme bundle for Contao Open Source CMS
 *
 * Copyright (C) pdir GmbH <develop@pdir.dev>
 */

namespace Pdir\CreuvaThemeBundle\Design;

use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Exception as DbalException;
use Symfony\Component\DependencyInjection\Attribute\Autowire;

/**
 * SVG version of the favicon of the website root: a file with the same name and
 * the extension "svg" next to it (e.g. design/favicon.ico → design/favicon.svg).
 */
class FaviconResolver
{
    /**
     * @var array<int, string|null>
     */
    private array $icons = [];

    public function __construct(
        private readonly Connection $connection,
        #[Autowire(param: 'kernel.project_dir')]
        private readonly string $projectDir,
    ) {
    }

    /**
     * @param array<string, mixed> $page page with details (rootId)
     *
     * @return string|null e.g. "files/design/favicon.svg?v=1760600000"
     */
    public function resolve(array $page): string|null
    {
        $rootId = (int) ($page['rootId'] ?? 0);

        if ($rootId < 1) {
            return null;
        }

        if (!\array_key_exists($rootId, $this->icons)) {
            $this->icons[$rootId] = $this->findSvg($rootId);
        }

        return $this->icons[$rootId];
    }

    /**
     * Path of the SVG file next to the given favicon path (e.g. "files/design/favicon.ico").
     */
    public static function getSvgPath(string $faviconPath): string
    {
        return preg_replace('#\.[^./]+$#', '', $faviconPath).'.svg';
    }

    private function findSvg(int $rootId): string|null
    {
        try {
            $path = $this->connection->fetchOne(
                "SELECT f.path FROM tl_page p INNER JOIN tl_files f ON f.uuid = p.favicon WHERE p.id = ? AND f.type = 'file'",
                [$rootId],
            );
        } catch (DbalException) {
            // The database has not been migrated yet
            return null;
        }

        if (!\is_string($path) || '' === $path) {
            return null;
        }

        $svg = self::getSvgPath($path);
        $file = $this->projectDir.'/'.$svg;

        return is_file($file) ? $svg.'?v='.filemtime($file) : null;
    }
}
