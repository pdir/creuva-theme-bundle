<?php

declare(strict_types=1);

/*
 * Creuva theme bundle for Contao Open Source CMS
 *
 * Copyright (C) pdir GmbH <develop@pdir.dev>
 */

namespace Pdir\CreuvaThemeBundle\EventListener\DataContainer;

use Contao\CoreBundle\DependencyInjection\Attribute\AsCallback;
use Contao\DataContainer;

/**
 * Image and text of the hero element are optional (e.g. a hero with headline
 * and buttons only), while the core fields singleSRC and text are mandatory.
 */
#[AsCallback(table: 'tl_content', target: 'config.onload')]
class HeroOptionalFieldsListener
{
    public function __invoke(DataContainer|null $dc = null): void
    {
        if (!$dc?->id || 'creuva_hero' !== ($dc->getCurrentRecord()['type'] ?? null)) {
            return;
        }

        $GLOBALS['TL_DCA']['tl_content']['fields']['singleSRC']['eval']['mandatory'] = false;
        $GLOBALS['TL_DCA']['tl_content']['fields']['text']['eval']['mandatory'] = false;
    }
}
