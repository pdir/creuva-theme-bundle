<?php

declare(strict_types=1);

/*
 * Creuva theme bundle for Contao Open Source CMS
 *
 * Copyright (C) pdir GmbH <develop@pdir.dev>
 */

namespace Pdir\CreuvaThemeBundle\Controller\ContentElement;

use Contao\ContentModel;
use Contao\CoreBundle\Controller\ContentElement\AbstractContentElementController;
use Contao\CoreBundle\DependencyInjection\Attribute\AsContentElement;
use Contao\CoreBundle\Image\Studio\Studio;
use Contao\CoreBundle\Twig\FragmentTemplate;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Header image at the top of a page: over the full width or the content width,
 * in a fixed height or the aspect ratio of the image, optionally with a kicker
 * and a headline (e.g. the h1 of the page) on a gradient.
 */
#[AsContentElement(type: 'creuva_page_header', category: 'creuva', template: 'content_element/creuva_page_header')]
class PageHeaderController extends AbstractContentElementController
{
    public const WIDTHS = ['full', 'content'];

    public const HEIGHTS = ['small', 'medium', 'large', 'auto'];

    public const ALIGNMENTS = ['bottom', 'center'];

    public function __construct(private readonly Studio $studio)
    {
    }

    protected function getResponse(FragmentTemplate $template, ContentModel $model, Request $request): Response
    {
        $template->set('kicker', $model->creuvaKicker ?: '');
        $template->set('width', $this->option($model->creuvaPageHeaderWidth, self::WIDTHS));
        $template->set('height', $this->option($model->creuvaPageHeaderHeight, self::HEIGHTS, 'medium'));
        $template->set('align', $this->option($model->creuvaPageHeaderAlign, self::ALIGNMENTS));

        $template->set('image', $this->studio
            ->createFigureBuilder()
            ->fromUuid($model->singleSRC ?: '')
            ->setSize($model->size)
            ->buildIfResourceExists(),
        );

        return $template->getResponse();
    }

    /**
     * @param list<string> $options
     */
    private function option(string|null $value, array $options, string|null $default = null): string
    {
        return \in_array($value, $options, true) ? $value : ($default ?? $options[0]);
    }
}
