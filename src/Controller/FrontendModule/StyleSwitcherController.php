<?php

declare(strict_types=1);

/*
 * Creuva theme bundle for Contao Open Source CMS
 *
 * Copyright (C) pdir GmbH <develop@pdir.de>
 */

namespace Pdir\CreuvaThemeBundle\Controller\FrontendModule;

use Contao\CoreBundle\Controller\FrontendModule\AbstractFrontendModuleController;
use Contao\CoreBundle\Csrf\ContaoCsrfTokenManager;
use Contao\CoreBundle\DependencyInjection\Attribute\AsFrontendModule;
use Contao\CoreBundle\Security\Authentication\Token\TokenChecker;
use Contao\CoreBundle\Twig\FragmentTemplate;
use Contao\LayoutModel;
use Contao\ModuleModel;
use Contao\UserModel;
use Pdir\CreuvaThemeBundle\Design\DesignResolver;
use Pdir\CreuvaThemeBundle\Design\DesignSettings;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

/**
 * Front end preview of the color schemes, color modes, typography and corner radii
 * (e.g. for a demo website). The choice is stored in the browser; in the front
 * end preview, administrators can also save it in the website root.
 */
#[AsFrontendModule(type: 'creuva_style_switcher', category: 'creuva', template: 'frontend_module/creuva_style_switcher')]
class StyleSwitcherController extends AbstractFrontendModuleController
{
    public function __construct(
        private readonly DesignSettings $designSettings,
        private readonly DesignResolver $designResolver,
        private readonly TokenChecker $tokenChecker,
        private readonly RequestStack $requestStack,
        private readonly ContaoCsrfTokenManager $tokenManager,
        private readonly UrlGeneratorInterface $urlGenerator,
    ) {
    }

    protected function getResponse(FragmentTemplate $template, ModuleModel $model, Request $request): Response
    {
        $page = $this->getPageModel();
        $canSave = $page && $this->canSave();

        // Option "Nur für angemeldete Backend-Benutzer": visitors do not see the
        // switcher (and their stored choice is not applied); logged in back end
        // users see it in the front end and in the preview
        if ($model->creuvaPreviewOnly && !$this->tokenChecker->hasBackendUser()) {
            return new Response();
        }

        $layout = $page ? $this->getContaoAdapter(LayoutModel::class)->findById($page->layout) : null;
        $settings = $this->designResolver->resolve($page?->row() ?? [], $layout?->row());

        $template->set('schemes', $this->designSettings->getSchemes($request->getLocale()));
        $template->set('variants', DesignSettings::VARIANTS);
        $template->set('typographies', DesignSettings::TYPOGRAPHIES);
        $template->set('radii', DesignSettings::RADII);
        $template->set('defaults', [
            'scheme' => $settings['scheme'] ?: $this->designSettings->getDefaultScheme(),
            'theme' => $settings['variant'],
            'typography' => $settings['typography'],
            'radius' => $settings['radius'],
        ]);

        $template->set('save', $canSave ? [
            'url' => $this->urlGenerator->generate('creuva_style_switcher_save'),
            'root' => (int) $page->rootId,
            'token' => $this->tokenManager->getDefaultTokenValue(),
        ] : null);

        return $template->getResponse();
    }

    /**
     * Only administrators can save the settings, and only in the front end
     * preview (the module is rendered in a sub-request).
     */
    private function canSave(): bool
    {
        if (!$this->requestStack->getMainRequest()?->attributes->get('_preview')) {
            return false;
        }

        $username = $this->tokenChecker->getBackendUsername();

        if (null === $username) {
            return false;
        }

        $user = $this->getContaoAdapter(UserModel::class)->findByUsername($username);

        return null !== $user && $user->admin && !$user->disable;
    }
}
