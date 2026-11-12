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
use Contao\CoreBundle\Twig\FragmentTemplate;
use Pdir\CreuvaThemeBundle\Design\DesignSettings;
use Pdir\CreuvaThemeBundle\Image\LogoImages;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Logo bar inside the content (e.g. clients or partners): no background of its
 * own, the section (article) provides it. The link of a logo comes from the
 * metadata of the file.
 */
#[AsContentElement(type: 'creuva_logos', category: 'creuva', template: 'content_element/creuva_logos')]
class LogosController extends AbstractContentElementController
{
    public function __construct(private readonly LogoImages $logoImages)
    {
    }

    protected function getResponse(FragmentTemplate $template, ContentModel $model, Request $request): Response
    {
        $logos = $this->logoImages->build($model->multiSRC, $model->size);

        if (!$logos) {
            return new Response();
        }

        $template->set('logos', $logos);
        $template->set('height', preg_match(DesignSettings::LOGO_HEIGHT_PATTERN, (string) $model->creuvaLogosHeight) ? (string) $model->creuvaLogosHeight : null);

        return $template->getResponse();
    }
}
