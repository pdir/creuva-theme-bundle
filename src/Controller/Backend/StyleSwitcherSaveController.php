<?php

declare(strict_types=1);

/*
 * Creuva theme bundle for Contao Open Source CMS
 *
 * Copyright (C) pdir GmbH <develop@pdir.de>
 */

namespace Pdir\CreuvaThemeBundle\Controller\Backend;

use Contao\BackendUser;
use Contao\CoreBundle\Cache\CacheTagManager;
use Contao\CoreBundle\Csrf\ContaoCsrfTokenManager;
use Doctrine\DBAL\Connection;
use Pdir\CreuvaThemeBundle\Design\DesignSettings;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Attribute\AsController;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Csrf\CsrfToken;

/**
 * Saves the choice of the style switcher in the website root. The endpoint is
 * called from the front end preview and is restricted to administrators.
 */
#[AsController]
#[Route('%contao.backend.route_prefix%/creuva/style-switcher', name: 'creuva_style_switcher_save', defaults: ['_scope' => 'backend', '_allow_preview' => true], methods: ['POST'])]
class StyleSwitcherSaveController
{
    public function __construct(
        private readonly Security $security,
        private readonly Connection $connection,
        private readonly DesignSettings $designSettings,
        private readonly CacheTagManager $cacheTagManager,
        private readonly ContaoCsrfTokenManager $tokenManager,
        #[Autowire('%contao.csrf_token_name%')]
        private readonly string $csrfTokenName,
    ) {
    }

    public function __invoke(Request $request): Response
    {
        $user = $this->security->getUser();

        if (!$user instanceof BackendUser || !$user->isAdmin) {
            return new JsonResponse(['success' => false], Response::HTTP_FORBIDDEN);
        }

        // Ajax requests are not checked by Contao's request token listener
        if (!$this->tokenManager->isTokenValid(new CsrfToken($this->csrfTokenName, (string) $request->request->get('REQUEST_TOKEN')))) {
            return new JsonResponse(['success' => false], Response::HTTP_BAD_REQUEST);
        }

        $scheme = (string) $request->request->get('scheme');
        $variant = (string) $request->request->get('theme');
        $radius = (string) $request->request->get('radius');

        if (
            !$this->designSettings->hasScheme($scheme)
            || !\in_array($variant, DesignSettings::VARIANTS, true)
            || !\in_array($radius, DesignSettings::RADII, true)
        ) {
            return new JsonResponse(['success' => false], Response::HTTP_BAD_REQUEST);
        }

        $rootId = (int) $this->connection->fetchOne(
            "SELECT id FROM tl_page WHERE id = ? AND type IN ('root', 'rootfallback')",
            [$request->request->getInt('root')],
        );

        if (!$rootId) {
            return new JsonResponse(['success' => false], Response::HTTP_NOT_FOUND);
        }

        $this->connection->update(
            'tl_page',
            ['creuvaScheme' => $scheme, 'creuvaVariant' => $variant, 'creuvaRadius' => $radius, 'tstamp' => time()],
            ['id' => $rootId],
        );

        // Every page of the website is tagged with its root page (page trail)
        $this->cacheTagManager->invalidateTags(['contao.db.tl_page.'.$rootId]);

        return new JsonResponse(['success' => true]);
    }
}
