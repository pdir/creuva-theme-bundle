<?php

declare(strict_types=1);

/*
 * Creuva theme bundle for Contao Open Source CMS
 *
 * Copyright (C) pdir GmbH <develop@pdir.de>
 */

namespace Pdir\CreuvaThemeBundle\OpenGraph;

use Pdir\CreuvaThemeBundle\Image\OpenGraphImage;

/**
 * Data for the Open Graph tags of the page layout.
 */
class OpenGraph
{
    /**
     * Region for languages without one (og:locale needs language_TERRITORY),
     * otherwise the language code in upper case is used (de → de_DE).
     */
    private const DEFAULT_REGIONS = [
        'cs' => 'CZ', 'da' => 'DK', 'el' => 'GR', 'en' => 'US', 'et' => 'EE', 'ja' => 'JP',
        'ko' => 'KR', 'nb' => 'NO', 'nn' => 'NO', 'sl' => 'SI', 'sr' => 'RS', 'sv' => 'SE',
        'uk' => 'UA', 'zh' => 'CN',
    ];

    public function __construct(
        private readonly OpenGraphImage $image,
        private readonly OpenGraphArticle $article,
    ) {
    }

    /**
     * @param array<string, mixed> $page page data of the page layout
     *
     * @return array{type: string, locale: string, image: array{src: string, width: int, height: int, alt: string}|null, published: string|null, modified: string|null}
     */
    public function resolve(array $page): array
    {
        $isArticle = $this->article->isActive();

        return [
            'type' => $isArticle ? 'article' : 'website',
            'locale' => self::locale((string) ($page['language'] ?? '')),
            'image' => $this->image->resolve($page),
            'published' => $isArticle ? self::date($this->article->getPublished()) : null,
            'modified' => $isArticle ? self::date($this->article->getModified()) : null,
        ];
    }

    /**
     * Language of the page as og:locale: "de" → "de_DE", "de-CH" → "de_CH".
     */
    public static function locale(string $language): string
    {
        [$lang, $region] = array_pad(preg_split('/[-_]/', $language, 2) ?: [], 2, '');
        $lang = strtolower($lang);

        if ('' === $lang) {
            return '';
        }

        return $lang.'_'.strtoupper('' !== $region ? $region : (self::DEFAULT_REGIONS[$lang] ?? $lang));
    }

    private static function date(int|null $timestamp): string|null
    {
        return null === $timestamp ? null : date(\DATE_ATOM, $timestamp);
    }
}
