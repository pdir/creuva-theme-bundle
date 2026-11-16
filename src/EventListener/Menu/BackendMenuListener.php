<?php

declare(strict_types=1);

/*
 * Creuva theme bundle for Contao Open Source CMS
 *
 * Copyright (C) pdir GmbH <develop@pdir.dev>
 */

namespace Pdir\CreuvaThemeBundle\EventListener\Menu;

use Contao\CoreBundle\Event\MenuEvent;
use Pdir\CreuvaThemeBundle\Controller\Backend\InstallerController;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\EventDispatcher\Attribute\AsEventListener;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\Routing\RouterInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

/**
 * Adds the back end page "Creuva" (theme setup and demo import) to the
 * "Layout" category, for administrators only.
 */
#[AsEventListener]
class BackendMenuListener
{
    public function __construct(
        private readonly Security $security,
        private readonly RouterInterface $router,
        private readonly RequestStack $requestStack,
        private readonly TranslatorInterface $translator,
    ) {
    }

    public function __invoke(MenuEvent $event): void
    {
        if ('mainMenu' !== $event->getTree()->getName() || !$this->security->isGranted('ROLE_ADMIN')) {
            return;
        }

        $category = $event->getTree()->getChild('design');

        if (!$category) {
            return;
        }

        $controller = $this->requestStack->getCurrentRequest()?->attributes->get('_controller');

        $category->addChild(
            $event->getFactory()
                ->createItem('creuva')
                ->setLabel('MOD.creuva.0')
                ->setExtra('translation_domain', 'contao_modules')
                ->setUri($this->router->generate(InstallerController::ROUTE))
                ->setLinkAttribute('class', 'navigation creuva')
                ->setLinkAttribute('title', $this->translator->trans('MOD.creuva.1', [], 'contao_modules'))
                ->setCurrent(\is_string($controller) && str_starts_with($controller, InstallerController::class)),
        );
    }
}
