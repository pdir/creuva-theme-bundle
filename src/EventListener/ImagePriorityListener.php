<?php

declare(strict_types=1);

/*
 * Creuva theme bundle for Contao Open Source CMS
 *
 * Copyright (C) pdir GmbH <develop@pdir.dev>
 */

namespace Pdir\CreuvaThemeBundle\EventListener;

use Contao\CoreBundle\Routing\ScopeMatcher;
use Symfony\Component\EventDispatcher\Attribute\AsEventListener;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Symfony\Component\HttpKernel\Event\ResponseEvent;

/**
 * Loads the first large image of a page with priority (usually the LCP image):
 * the first image with a width of at least 400 pixels in the first article of
 * the main area (Creuva layout, <main id="main">) gets fetchpriority="high" and
 * no loading="lazy". Pages that start with text keep all images unchanged, and
 * so do pages where an image already has a fetchpriority.
 */
// After Symfony's ResponseListener (priority 0), which sets the Content-Type
#[AsEventListener(priority: -16)]
class ImagePriorityListener
{
    private const MIN_WIDTH = 400;

    public function __construct(private readonly ScopeMatcher $scopeMatcher)
    {
    }

    public function __invoke(ResponseEvent $event): void
    {
        if (!$this->scopeMatcher->isFrontendMainRequest($event)) {
            return;
        }

        $response = $event->getResponse();

        if ($response instanceof StreamedResponse || $response instanceof BinaryFileResponse || !str_contains((string) $response->headers->get('Content-Type'), 'text/html')) {
            return;
        }

        $content = (string) $response->getContent();

        if (false === ($start = strpos($content, '<main id="main"'))) {
            return;
        }

        // The first article only, the following ones are usually below the fold
        $end = strpos($content, '</main>', $start) ?: \strlen($content);
        $first = strpos($content, 'class="mod_article', $start);

        if (false !== $first && false !== ($second = strpos($content, 'class="mod_article', $first + 1)) && $second < $end) {
            $end = $second;
        }

        $region = substr($content, $start, $end - $start);

        if (str_contains($region, 'fetchpriority=') || !preg_match_all('/<img\b[^>]*>/i', $region, $matches, PREG_OFFSET_CAPTURE)) {
            return;
        }

        foreach ($matches[0] as [$tag, $offset]) {
            if (!preg_match('/\swidth="(\d+)"/', $tag, $width) || (int) $width[1] < self::MIN_WIDTH) {
                continue;
            }

            $priority = preg_replace(['/\sloading="lazy"/i', '/^<img\b/i'], ['', '<img fetchpriority="high"'], $tag);
            $response->setContent(substr_replace($content, $priority, $start + $offset, \strlen($tag)));

            return;
        }
    }
}
