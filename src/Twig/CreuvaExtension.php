<?php

declare(strict_types=1);

/*
 * Creuva theme bundle for Contao Open Source CMS
 *
 * Copyright (C) pdir GmbH <develop@pdir.de>
 */

namespace Pdir\CreuvaThemeBundle\Twig;

use Pdir\CreuvaThemeBundle\Design\DesignResolver;
use Pdir\CreuvaThemeBundle\Design\FaviconResolver;
use Pdir\CreuvaThemeBundle\Design\StylesheetResolver;
use Twig\Extension\AbstractExtension;
use Twig\TwigFunction;

class CreuvaExtension extends AbstractExtension
{
    public function __construct(
        private readonly DesignResolver $designResolver,
        private readonly StylesheetResolver $stylesheetResolver,
        private readonly FaviconResolver $faviconResolver,
    ) {
    }

    public function getFunctions(): array
    {
        return [
            // Usage: {% set creuva = creuva_design(page, layout) %}
            new TwigFunction('creuva_design', $this->designResolver->resolve(...)),
            // Usage: {% for stylesheet in creuva_stylesheets(layout) %}
            new TwigFunction('creuva_stylesheets', $this->stylesheetResolver->resolve(...)),
            // Usage: {% set favicon_svg = creuva_favicon_svg(page) %}
            new TwigFunction('creuva_favicon_svg', $this->faviconResolver->resolve(...)),
        ];
    }
}
