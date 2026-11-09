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
use Pdir\CreuvaThemeBundle\OpenGraph\OpenGraphArticle;

/**
 * Remembers the news of a news reader for the Open Graph tags of the page.
 */
#[AsHook('parseArticles')]
class OpenGraphArticleListener
{
    public function __construct(private readonly OpenGraphArticle $article)
    {
    }

    /**
     * @param array<string, mixed> $article
     */
    public function __invoke(FrontendTemplate $template, array $article, Module $module): void
    {
        if ('newsreader' === $module->type) {
            $this->article->set($article);
        }
    }
}
