<?php

declare(strict_types=1);

/*
 * Creuva theme bundle for Contao Open Source CMS
 *
 * Copyright (C) pdir GmbH <develop@pdir.de>
 */

namespace Pdir\CreuvaThemeBundle\Installer;

use FOS\HttpCache\CacheInvalidator;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Automator tasks that remove cached pages, generated images and XML files after
 * contents have been deleted (creuva:reset, creuva:install --replace).
 */
class CachePurger
{
    public function __construct(
        #[Autowire(service: 'service_container')]
        private readonly ContainerInterface $container,
    ) {
    }

    /**
     * The shared page cache is only purged if a cache manager that can clear it is
     * configured: otherwise the automator logs "Cannot purge the shared cache;
     * invalid reverse proxy configuration" (e.g. on the command line).
     *
     * @return list<string>
     */
    public function getAutomatorTasks(): array
    {
        $tasks = ['purgeImageCache', 'purgeXmlFiles'];

        if ($this->container->has('fos_http_cache.cache_manager') && $this->container->get('fos_http_cache.cache_manager')->supports(CacheInvalidator::CLEAR)) {
            array_unshift($tasks, 'purgePageCache');
        }

        return $tasks;
    }
}
