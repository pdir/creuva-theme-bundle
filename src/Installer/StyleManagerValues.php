<?php

declare(strict_types=1);

/*
 * Creuva theme bundle for Contao Open Source CMS
 *
 * Copyright (C) pdir GmbH <develop@pdir.de>
 */

namespace Pdir\CreuvaThemeBundle\Installer;

use Symfony\Component\Yaml\Yaml;

/**
 * Derives the selection of the Style Manager field (styleManager) from the CSS
 * classes of a record, so that the classes of the demo elements show up as
 * selected in the back end instead of as free text in the CSS class field.
 *
 * Uses the bundle configuration contao/templates/style-manager-creuva.yaml.
 */
class StyleManagerValues
{
    private const CONFIG = __DIR__.'/../../contao/templates/style-manager-creuva.yaml';

    /**
     * Option in the record → field of the style group (extend* flags).
     */
    private const TABLES = [
        'tl_content' => 'extendContentElement',
        'tl_article' => 'extendArticle',
        'tl_module' => 'extendModule',
    ];

    /**
     * @var list<array{key: string, classes: list<string>, flag: string, elements: list<string>|null}>|null
     */
    private array|null $groups = null;

    /**
     * Returns the serialized value of the styleManager field for the given
     * classes, or null if none of them belongs to a style group.
     *
     * @param string      $table   tl_content, tl_article or tl_module
     * @param string|null $type    element type (content elements only)
     * @param string      $classes CSS classes separated by spaces
     */
    public function resolve(string $table, string|null $type, string $classes): string|null
    {
        if (!isset(self::TABLES[$table]) || '' === trim($classes)) {
            return null;
        }

        $tokens = preg_split('/\s+/', trim($classes)) ?: [];
        $values = [];

        foreach ($this->getGroups() as $group) {
            if ($group['flag'] !== self::TABLES[$table]) {
                continue;
            }

            if ('tl_content' === $table && null !== $group['elements'] && !\in_array($type, $group['elements'], true)) {
                continue;
            }

            // One class per group (select field); the first match wins
            foreach ($tokens as $token) {
                if (\in_array($token, $group['classes'], true)) {
                    $values[$group['key']] = $token;
                    break;
                }
            }
        }

        return $values ? serialize($values) : null;
    }

    /**
     * @return list<array{key: string, classes: list<string>, flag: string, elements: list<string>|null}>
     */
    private function getGroups(): array
    {
        if (null !== $this->groups) {
            return $this->groups;
        }

        $this->groups = [];

        if (!is_file(self::CONFIG)) {
            return $this->groups;
        }

        foreach (Yaml::parseFile(self::CONFIG) ?? [] as $archive => $data) {
            foreach ($data['children'] ?? [] as $alias => $group) {
                foreach (self::TABLES as $flag) {
                    if (empty($group[$flag])) {
                        continue;
                    }

                    $this->groups[] = [
                        // Same as StyleManager::generateAlias()
                        'key' => $archive.'_'.$alias,
                        'classes' => array_map(strval(...), array_keys($group['cssClasses'] ?? [])),
                        'flag' => $flag,
                        'elements' => 'extendContentElement' === $flag ? ($group['contentElements'] ?? []) : null,
                    ];
                }
            }
        }

        return $this->groups;
    }
}
