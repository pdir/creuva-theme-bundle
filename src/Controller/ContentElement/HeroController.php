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
use Contao\CoreBundle\InsertTag\InsertTagParser;
use Contao\CoreBundle\String\HtmlAttributes;
use Contao\CoreBundle\Twig\FragmentTemplate;
use Contao\Validator;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Hero section with kicker, headline, text, up to two buttons and an image
 * with organic mask (see theme-idee/ "Digitale Erlebnisse, die begeistern").
 */
#[AsContentElement(type: 'creuva_hero', category: 'creuva', template: 'content_element/creuva_hero')]
class HeroController extends AbstractContentElementController
{
    public function __construct(
        private readonly Studio $studio,
        private readonly InsertTagParser $insertTagParser,
    ) {
    }

    protected function getResponse(FragmentTemplate $template, ContentModel $model, Request $request): Response
    {
        $template->set('kicker', $model->creuvaKicker ?: '');
        $template->set('headline_accent', $model->creuvaHeadlineAccent ?: '');
        $template->set('text', $model->text ?: '');
        $template->set('badge', $model->creuvaBadge ?: '');
        $template->set('mask', $model->creuvaHeroMask ?: 'organic');
        $template->set('badge_style', $model->creuvaBadgeStyle ?: 'list');
        $template->set('badge_position', $model->creuvaBadgePosition ?: 'bottom-start');
        $template->set('badge_headline', $model->creuvaBadgeHeadline ?: '');
        $template->set('badge_icon', $model->creuvaBadgeIcon ?: '');
        $template->set('badge_href', $model->creuvaBadgeUrl ? $this->buildHref($model->creuvaBadgeUrl, $request) : '');
        $template->set('badge_rating', $this->parseRating((string) $model->creuvaBadge));

        $template->set('image', $this->studio
            ->createFigureBuilder()
            ->fromUuid($model->singleSRC ?: '')
            ->setSize($model->size)
            ->buildIfResourceExists(),
        );

        $template->set('buttons', array_values(array_filter([
            $this->buildButton($model->creuvaUrl1, $model->creuvaLinkTitle1, 'primary', $request),
            $this->buildButton($model->creuvaUrl2, $model->creuvaLinkTitle2, 'secondary', $request),
        ])));

        return $template->getResponse();
    }

    /**
     * @return array{text: string, variant: string, attributes: HtmlAttributes}|null
     */
    private function buildButton(string|null $url, string|null $title, string $variant, Request $request): array|null
    {
        if (!$url || !$title) {
            return null;
        }

        return [
            'text' => $title,
            'variant' => $variant,
            'attributes' => (new HtmlAttributes())->set('href', $this->buildHref($url, $request)),
        ];
    }

    /**
     * Stars of the badge style "rating": the first number in the first line
     * (e.g. "4,9", "4.9/5" or "4,9 von 5"), limited to 0–5; five stars if
     * the line has no number. Returned as a CSS value ("4.90").
     */
    private function parseRating(string $badge): string
    {
        $firstLine = trim(strtok($badge, "\n") ?: '');

        if (!preg_match('/\d+(?:[.,]\d+)?/', $firstLine, $matches)) {
            return '5';
        }

        $value = max(0.0, min(5.0, (float) str_replace(',', '.', $matches[0])));

        return \sprintf('%.2F', $value);
    }

    private function buildHref(string $url, Request $request): string
    {
        $href = $this->insertTagParser->replaceInline($url);

        if (Validator::isRelativeUrl($href)) {
            $href = $request->getBasePath().'/'.$href;
        }

        return $href;
    }
}
