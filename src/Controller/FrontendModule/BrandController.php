<?php

declare(strict_types=1);

/*
 * Creuva theme bundle for Contao Open Source CMS
 *
 * Copyright (C) pdir GmbH <develop@pdir.de>
 */

namespace Pdir\CreuvaThemeBundle\Controller\FrontendModule;

use Contao\CoreBundle\DependencyInjection\Attribute\AsFrontendModule;
use Contao\CoreBundle\Twig\FragmentTemplate;
use Contao\ModuleModel;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Brand block for the footer: logo, claim and social media links.
 */
#[AsFrontendModule(type: 'creuva_brand', category: 'creuva', template: 'frontend_module/creuva_brand')]
class BrandController extends AbstractBrandController
{
    /**
     * Host name fragments mapped to the icon/label of the social link.
     */
    private const PLATFORMS = [
        'instagram.' => 'Instagram',
        'linkedin.' => 'LinkedIn',
        'x.com' => 'X',
        'twitter.' => 'X',
        'youtube.' => 'YouTube',
        'youtu.be' => 'YouTube',
        'facebook.' => 'Facebook',
        'xing.' => 'Xing',
        'github.' => 'GitHub',
        'tiktok.' => 'TikTok',
        'mastodon.' => 'Mastodon',
    ];

    protected function getResponse(FragmentTemplate $template, ModuleModel $model, Request $request): Response
    {
        $template->set('brand', $this->buildBrand($model, $request));
        $template->set('claim', $model->creuvaClaim ?: '');
        $template->set('social_links', $this->parseSocialLinks($model->creuvaSocialLinks ?: ''));

        return $template->getResponse();
    }

    /**
     * @return list<array{href: string, platform: string, label: string}>
     */
    private function parseSocialLinks(string $value): array
    {
        $links = [];

        foreach (preg_split('/\R/', $value) ?: [] as $line) {
            $href = trim($line);

            if (!preg_match('#^https?://#i', $href)) {
                continue;
            }

            $host = strtolower(parse_url($href, PHP_URL_HOST) ?: '');
            $label = 'Link';

            foreach (self::PLATFORMS as $needle => $platform) {
                if (str_contains($host, $needle)) {
                    $label = $platform;
                    break;
                }
            }

            $links[] = ['href' => $href, 'platform' => strtolower($label), 'label' => $label];
        }

        return $links;
    }
}
