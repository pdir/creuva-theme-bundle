<?php

declare(strict_types=1);

/*
 * Creuva theme bundle for Contao Open Source CMS
 *
 * Copyright (C) pdir GmbH <develop@pdir.de>
 */

namespace Pdir\CreuvaThemeBundle\Installer;

final class InstallResult
{
    public int $themeId = 0;

    public int $layoutId = 0;

    /**
     * @var array<int, string> module ID => name
     */
    public array $modules = [];

    /**
     * @var array<string, int> module key (navigation, header, brand, …) => module ID
     */
    public array $moduleIds = [];

    /**
     * @var array<string, int> key (logo, …) => image size ID
     */
    public array $imageSizes = [];

    /**
     * @var list<int>
     */
    public array $rootPages = [];

    /**
     * @var array<string, int> page key => page ID (only for created pages)
     */
    public array $pages = [];

    /**
     * @var array<string, int> page key => article ID (only for created pages)
     */
    public array $articles = [];

    public bool $classSetAssigned = false;

    /**
     * @var list<string>
     */
    public array $warnings = [];
}
