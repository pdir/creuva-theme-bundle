<?php

declare(strict_types=1);

/*
 * Creuva theme bundle for Contao Open Source CMS
 *
 * Copyright (C) pdir GmbH <develop@pdir.de>
 */

namespace Pdir\CreuvaThemeBundle\Twig;

use Pdir\CreuvaThemeBundle\Design\DesignResolver;
use Twig\Extension\AbstractExtension;
use Twig\TwigFunction;

class CreuvaExtension extends AbstractExtension
{
    public function __construct(private readonly DesignResolver $designResolver)
    {
    }

    public function getFunctions(): array
    {
        return [
            // Usage: {% set creuva = creuva_design(page, layout) %}
            new TwigFunction('creuva_design', $this->designResolver->resolve(...)),
        ];
    }
}
