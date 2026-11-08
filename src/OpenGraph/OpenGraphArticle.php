<?php

declare(strict_types=1);

/*
 * Creuva theme bundle for Contao Open Source CMS
 *
 * Copyright (C) pdir GmbH <develop@pdir.de>
 */

namespace Pdir\CreuvaThemeBundle\OpenGraph;

use Contao\StringUtil;
use Symfony\Contracts\Service\ResetInterface;

/**
 * News shown by a news reader of the current page (set by
 * OpenGraphArticleListener): the link preview then shows it as an article
 * with its image.
 */
class OpenGraphArticle implements ResetInterface
{
    private array|null $news = null;

    /**
     * @param array<string, mixed> $news row of tl_news
     */
    public function set(array $news): void
    {
        $this->news = $news;
    }

    public function isActive(): bool
    {
        return null !== $this->news;
    }

    /**
     * @return string|null UUID of the news image
     */
    public function getImageUuid(): string|null
    {
        $uuid = $this->news['singleSRC'] ?? null;

        if (empty($this->news['addImage']) || !\is_string($uuid) || '' === $uuid) {
            return null;
        }

        return 16 === \strlen($uuid) ? StringUtil::binToUuid($uuid) : $uuid;
    }

    /**
     * Alternative text entered in the news itself ("Overwrite metadata").
     */
    public function getImageAlt(): string|null
    {
        if (empty($this->news['overwriteMeta'])) {
            return null;
        }

        $alt = trim((string) ($this->news['alt'] ?? ''));

        return '' === $alt || str_starts_with($alt, '{{empty') ? null : $alt;
    }

    public function getPublished(): int|null
    {
        return (int) ($this->news['date'] ?? 0) ?: null;
    }

    public function getModified(): int|null
    {
        return (int) ($this->news['tstamp'] ?? 0) ?: null;
    }

    public function reset(): void
    {
        $this->news = null;
    }
}
