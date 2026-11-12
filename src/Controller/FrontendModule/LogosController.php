<?php

declare(strict_types=1);

/*
 * Creuva theme bundle for Contao Open Source CMS
 *
 * Copyright (C) pdir GmbH <develop@pdir.dev>
 */

namespace Pdir\CreuvaThemeBundle\Controller\FrontendModule;

use Contao\CoreBundle\Controller\FrontendModule\AbstractFrontendModuleController;
use Contao\CoreBundle\DependencyInjection\Attribute\AsFrontendModule;
use Contao\CoreBundle\Twig\FragmentTemplate;
use Contao\ModuleModel;
use Pdir\CreuvaThemeBundle\Design\DesignSettings;
use Pdir\CreuvaThemeBundle\Image\LogoImages;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Logo bar (e.g. partners or sponsors) over the full width, e.g. in the slot
 * "prefooter"; inside the content there is the content element creuva_logos.
 * The link of a logo comes from the metadata of the file.
 */
#[AsFrontendModule(type: 'creuva_logos', category: 'creuva', template: 'frontend_module/creuva_logos')]
class LogosController extends AbstractFrontendModuleController
{
    public const VARIANTS = ['surface', 'primary', 'muted'];

    public function __construct(private readonly LogoImages $logoImages)
    {
    }

    protected function getResponse(FragmentTemplate $template, ModuleModel $model, Request $request): Response
    {
        $logos = $this->logoImages->build($model->multiSRC, $model->imgSize);

        if (!$logos) {
            return new Response();
        }

        $template->set('logos', $logos);
        $template->set('variant', \in_array($model->creuvaLogosVariant, self::VARIANTS, true) ? $model->creuvaLogosVariant : 'surface');
        $template->set('height', preg_match(DesignSettings::LOGO_HEIGHT_PATTERN, (string) $model->creuvaLogosHeight) ? (string) $model->creuvaLogosHeight : null);

        return $template->getResponse();
    }
}
