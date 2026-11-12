<?php

declare(strict_types=1);

/*
 * Creuva theme bundle for Contao Open Source CMS
 *
 * Copyright (C) pdir GmbH <develop@pdir.dev>
 */

namespace Pdir\CreuvaThemeBundle\Controller\FrontendModule;

use Contao\CoreBundle\Controller\FrontendModule\AbstractFrontendModuleController;
use Contao\CoreBundle\File\Metadata;
use Contao\CoreBundle\Image\Studio\Studio;
use Contao\CoreBundle\Routing\ContentUrlGenerator;
use Contao\ModuleModel;
use Contao\PageModel;
use Symfony\Component\HttpFoundation\Request;

/**
 * Shared logic of the header and brand module: logo (or text) linking to the
 * start page of the current website.
 */
abstract class AbstractBrandController extends AbstractFrontendModuleController
{
    public function __construct(
        protected readonly Studio $studio,
        protected readonly ContentUrlGenerator $urlGenerator,
    ) {
    }

    /**
     * @return array{href: string, logo: \Contao\CoreBundle\Image\Studio\Figure|null, text: string}
     */
    protected function buildBrand(ModuleModel $model, Request $request): array
    {
        $rootPage = null;

        if ($page = $this->getPageModel()) {
            $rootPage = $this->getContaoAdapter(PageModel::class)->findById($page->rootId);
        }

        $text = $model->creuvaLogoText ?: ($rootPage?->pageTitle ?: $rootPage?->title ?: '');

        $logo = $this->studio
            ->createFigureBuilder()
            ->fromUuid($model->creuvaLogo ?: '')
            ->setSize($model->imgSize)
        ;

        // The link is named by the visually hidden text; an alt text from the file
        // metadata would be read twice ("Regioner Regioner")
        if ('' !== $text) {
            $logo->setOverwriteMetadata(new Metadata([Metadata::VALUE_ALT => '']));
        }

        return [
            'href' => $this->getStartUrl($rootPage, $request),
            'logo' => $logo->buildIfResourceExists(),
            'text' => $text,
        ];
    }

    private function getStartUrl(PageModel|null $rootPage, Request $request): string
    {
        if ($rootPage) {
            try {
                return $this->urlGenerator->generate($rootPage);
            } catch (\Throwable) {
                // Fall back to the base path below
            }
        }

        return $request->getBasePath().'/';
    }
}
