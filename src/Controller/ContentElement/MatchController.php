<?php

declare(strict_types=1);

/*
 * Creuva theme bundle for Contao Open Source CMS
 *
 * Copyright (C) pdir GmbH <develop@pdir.de>
 */

namespace Pdir\CreuvaThemeBundle\Controller\ContentElement;

use Contao\ContentModel;
use Contao\CoreBundle\Controller\ContentElement\AbstractContentElementController;
use Contao\CoreBundle\DependencyInjection\Attribute\AsContentElement;
use Contao\CoreBundle\File\Metadata;
use Contao\CoreBundle\Image\Studio\Figure;
use Contao\CoreBundle\Image\Studio\Studio;
use Contao\CoreBundle\InsertTag\InsertTagParser;
use Contao\CoreBundle\Twig\FragmentTemplate;
use Contao\Date;
use Contao\Validator;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Match of two teams (e.g. the next match or the last result): competition,
 * teams with their crests, kick-off, venue, result and a button. Without a
 * result the kick-off time is shown between the teams.
 */
#[AsContentElement(type: 'creuva_match', category: 'creuva', template: 'content_element/creuva_match')]
class MatchController extends AbstractContentElementController
{
    public function __construct(
        private readonly Studio $studio,
        private readonly InsertTagParser $insertTagParser,
    ) {
    }

    protected function getResponse(FragmentTemplate $template, ContentModel $model, Request $request): Response
    {
        $template->set('kicker', $model->creuvaKicker ?: '');
        $template->set('venue', $model->creuvaVenue ?: '');
        $template->set('home', $this->buildTeam((string) $model->creuvaHomeTeam, $model->creuvaHomeLogo, $model->size));
        $template->set('away', $this->buildTeam((string) $model->creuvaAwayTeam, $model->creuvaAwayLogo, $model->size));

        // Result "3:1" (or "3 : 1", "3-1") as two numbers, otherwise as text (e.g. "abgesagt")
        $score = trim((string) $model->creuvaScore);
        $template->set('score', $score);
        $template->set('goals', preg_match('/^(\d+)\s*[:\-–]\s*(\d+)$/', $score, $m) ? [$m[1], $m[2]] : null);

        $template->set('kickoff', null);

        if ($timestamp = (int) $model->creuvaMatchDate) {
            $page = $this->getPageModel();
            $date = $this->getContaoAdapter(Date::class);

            $template->set('kickoff', [
                'datetime' => date('c', $timestamp),
                'day' => $date->parse('D', $timestamp),
                'date' => $date->parse($page?->dateFormat ?: 'd.m.Y', $timestamp),
                'time' => $date->parse($page?->timeFormat ?: 'H:i', $timestamp),
            ]);
        }

        $template->set('button', null);

        if ($model->creuvaUrl1 && $model->creuvaLinkTitle1) {
            $href = $this->insertTagParser->replaceInline($model->creuvaUrl1);

            if (Validator::isRelativeUrl($href)) {
                $href = $request->getBasePath().'/'.$href;
            }

            $template->set('button', ['href' => $href, 'text' => $model->creuvaLinkTitle1]);
        }

        return $template->getResponse();
    }

    /**
     * The crest is decorative (empty alternative text), the name is next to it.
     *
     * @return array{name: string, logo: Figure|null}
     */
    private function buildTeam(string $name, mixed $logo, mixed $size): array
    {
        return [
            'name' => $name,
            'logo' => $this->studio
                ->createFigureBuilder()
                ->fromUuid($logo ?: '')
                ->setSize($size)
                ->setMetadata(new Metadata([Metadata::VALUE_ALT => '']))
                ->buildIfResourceExists(),
        ];
    }
}
