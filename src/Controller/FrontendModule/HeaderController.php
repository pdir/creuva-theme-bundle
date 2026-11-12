<?php

declare(strict_types=1);

/*
 * Creuva theme bundle for Contao Open Source CMS
 *
 * Copyright (C) pdir GmbH <develop@pdir.dev>
 */

namespace Pdir\CreuvaThemeBundle\Controller\FrontendModule;

use Contao\CoreBundle\DependencyInjection\Attribute\AsFrontendModule;
use Contao\CoreBundle\InsertTag\InsertTagParser;
use Contao\CoreBundle\Image\Studio\Studio;
use Contao\CoreBundle\Routing\ContentUrlGenerator;
use Contao\CoreBundle\Twig\FragmentTemplate;
use Contao\ModuleModel;
use Contao\PageModel;
use Contao\Validator;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Site header with logo, main navigation, CTA button, search and mobile menu toggle.
 */
#[AsFrontendModule(type: 'creuva_header', category: 'creuva', template: 'frontend_module/creuva_header')]
class HeaderController extends AbstractBrandController
{
    public function __construct(
        Studio $studio,
        ContentUrlGenerator $urlGenerator,
        private readonly InsertTagParser $insertTagParser,
    ) {
        parent::__construct($studio, $urlGenerator);
    }

    protected function getResponse(FragmentTemplate $template, ModuleModel $model, Request $request): Response
    {
        $template->set('brand', $this->buildBrand($model, $request));
        $template->set('navigation', (int) $model->creuvaNavigation);
        $template->set('cta', null);

        if ($model->creuvaUrl1 && $model->creuvaLinkTitle1) {
            $href = $this->insertTagParser->replaceInline($model->creuvaUrl1);

            if (Validator::isRelativeUrl($href)) {
                $href = $request->getBasePath().'/'.$href;
            }

            $template->set('cta', ['href' => $href, 'text' => $model->creuvaLinkTitle1]);
        }

        $template->set('search', $this->buildSearch($model, $request));

        return $template->getResponse();
    }

    /**
     * Target and current keywords of the header search (null if it is disabled or
     * the results page does not exist).
     *
     * @return array{action: string, keywords: string}|null
     */
    private function buildSearch(ModuleModel $model, Request $request): array|null
    {
        if (!$model->creuvaSearch || !$model->creuvaSearchPage) {
            return null;
        }

        $page = $this->getContaoAdapter(PageModel::class)->findPublishedById((int) $model->creuvaSearchPage);

        if (null === $page) {
            return null;
        }

        try {
            $action = $this->urlGenerator->generate($page);
        } catch (\Throwable) {
            return null;
        }

        $keywords = $request->query->get('keywords');

        return ['action' => $action, 'keywords' => \is_string($keywords) ? trim($keywords) : ''];
    }
}
