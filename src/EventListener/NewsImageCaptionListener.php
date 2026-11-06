<?php

declare(strict_types=1);

/*
 * Creuva theme bundle for Contao Open Source CMS
 *
 * Copyright (C) pdir GmbH <develop@pdir.de>
 */

namespace Pdir\CreuvaThemeBundle\EventListener;

use Contao\CoreBundle\DependencyInjection\Attribute\AsHook;
use Contao\FrontendTemplate;
use Contao\Module;

/**
 * Caption of the news image: none in lists. The news reader only shows a caption
 * entered in the news itself ("Overwrite metadata"), not the one of the file
 * manager, which Contao would use for an empty field.
 */
#[AsHook('parseArticles')]
class NewsImageCaptionListener
{
    /**
     * @param array<string, mixed> $article
     */
    public function __invoke(FrontendTemplate $template, array $article, Module $module): void
    {
        $caption = !empty($article['overwriteMeta']) ? trim((string) ($article['caption'] ?? '')) : '';

        // {{empty}} is the way of Contao to empty a metadata field
        if (str_starts_with($caption, '{{empty')) {
            $caption = '';
        }

        $template->caption = 'newsreader' === $module->type ? $caption : '';
    }
}
