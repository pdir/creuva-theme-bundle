<?php

declare(strict_types=1);

/*
 * Creuva theme bundle for Contao Open Source CMS
 *
 * Copyright (C) pdir GmbH <develop@pdir.de>
 */

namespace Pdir\CreuvaThemeBundle\Controller\FrontendModule;

use Contao\CoreBundle\DependencyInjection\Attribute\AsFrontendModule;
use Contao\CoreBundle\InsertTag\InsertTagParser;
use Contao\CoreBundle\Image\Studio\Studio;
use Contao\CoreBundle\Routing\ContentUrlGenerator;
use Contao\CoreBundle\Twig\FragmentTemplate;
use Contao\ModuleModel;
use Contao\Validator;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Site header with logo, main navigation, CTA button and mobile menu toggle.
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

        return $template->getResponse();
    }
}
