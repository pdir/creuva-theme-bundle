<?php

declare(strict_types=1);

/*
 * Creuva theme bundle for Contao Open Source CMS
 *
 * Copyright (C) pdir GmbH <develop@pdir.de>
 */

namespace Pdir\CreuvaThemeBundle\Twig;

use Contao\StringUtil;
use Contao\Validator;
use Pdir\CreuvaThemeBundle\Design\DesignResolver;
use Pdir\CreuvaThemeBundle\Design\StylesheetResolver;
use Twig\Extension\AbstractExtension;
use Twig\TwigFunction;

class CreuvaExtension extends AbstractExtension
{
    public function __construct(
        private readonly DesignResolver $designResolver,
        private readonly StylesheetResolver $stylesheetResolver,
    ) {
    }

    public function getFunctions(): array
    {
        return [
            // Usage: {% set creuva = creuva_design(page, layout) %}
            new TwigFunction('creuva_design', $this->designResolver->resolve(...)),
            // Usage: {% for stylesheet in creuva_stylesheets(layout) %}
            new TwigFunction('creuva_stylesheets', $this->stylesheetResolver->resolve(...)),
            // Usage: {% set icons = creuva_uuids(data.creuvaIcons|default) %} → list of UUIDs for figure()
            new TwigFunction('creuva_uuids', $this->uuids(...)),
        ];
    }

    /**
     * Serialized binary UUIDs of a multiple file tree field (in the selected
     * order) as a list of string UUIDs.
     *
     * @return list<string>
     */
    private function uuids(mixed $value): array
    {
        $uuids = StringUtil::deserialize($value, true);

        return array_values(array_map(
            static fn (string $uuid): string => Validator::isBinaryUuid($uuid) ? StringUtil::binToUuid($uuid) : $uuid,
            array_filter($uuids, \is_string(...)),
        ));
    }
}
