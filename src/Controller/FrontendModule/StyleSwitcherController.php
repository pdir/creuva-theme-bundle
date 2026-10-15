<?php

declare(strict_types=1);

/*
 * Creuva theme bundle for Contao Open Source CMS
 *
 * Copyright (C) pdir GmbH <develop@pdir.de>
 */

namespace Pdir\CreuvaThemeBundle\Controller\FrontendModule;

use Contao\CoreBundle\Controller\FrontendModule\AbstractFrontendModuleController;
use Contao\CoreBundle\DependencyInjection\Attribute\AsFrontendModule;
use Contao\CoreBundle\Twig\FragmentTemplate;
use Contao\LayoutModel;
use Contao\ModuleModel;
use Pdir\CreuvaThemeBundle\Design\DesignSettings;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Front end preview of the color schemes, design variants and corner radii
 * (e.g. for a demo website). The choice is stored in the browser only.
 */
#[AsFrontendModule(type: 'creuva_style_switcher', category: 'creuva', template: 'frontend_module/creuva_style_switcher')]
class StyleSwitcherController extends AbstractFrontendModuleController
{
    public function __construct(private readonly DesignSettings $designSettings)
    {
    }

    protected function getResponse(FragmentTemplate $template, ModuleModel $model, Request $request): Response
    {
        $layout = null;

        if ($page = $this->getPageModel()) {
            $layout = $this->getContaoAdapter(LayoutModel::class)->findById($page->layout);
        }

        $variant = (string) ($layout?->creuvaVariant ?: 'light');
        $scheme = (string) ($layout?->creuvaScheme ?: '');

        if ('corporate' === $variant) {
            [$variant, $scheme] = ['light', $scheme ?: 'blue'];
        }

        $template->set('schemes', $this->designSettings->getSchemes($request->getLocale()));
        $template->set('variants', DesignSettings::VARIANTS);
        $template->set('radii', DesignSettings::RADII);
        $template->set('defaults', [
            'scheme' => $this->designSettings->hasScheme($scheme) ? $scheme : $this->designSettings->getDefaultScheme(),
            'theme' => $variant,
            'radius' => (string) ($layout?->creuvaRadius ?: 'large'),
        ]);

        return $template->getResponse();
    }
}
