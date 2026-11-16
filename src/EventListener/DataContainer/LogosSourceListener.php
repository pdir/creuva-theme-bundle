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
 * Only images can be selected as logos.
 */
#[AsCallback(table: 'tl_module', target: 'fields.multiSRC.load')]
#[AsCallback(table: 'tl_content', target: 'fields.multiSRC.load')]
class LogosSourceListener
{
    public function __invoke(mixed $value, DataContainer $dc): mixed
    {
        if ('creuva_logos' === ($dc->getCurrentRecord()['type'] ?? null)) {
            $GLOBALS['TL_DCA'][$dc->table]['fields']['multiSRC']['eval']['isGallery'] = true;
            $GLOBALS['TL_DCA'][$dc->table]['fields']['multiSRC']['eval']['extensions'] = '%contao.image.valid_extensions%';
        }

        return $value;
    }
}
