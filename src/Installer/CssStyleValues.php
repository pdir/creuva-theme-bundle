<?php

declare(strict_types=1);

/*
 * Creuva theme bundle for Contao Open Source CMS
 *
 * Copyright (C) pdir GmbH <develop@pdir.dev>
 */

namespace Pdir\CreuvaThemeBundle\Installer;

use Craffft\CssStyleSelectorBundle\Models\CssStyleSelectorModel;
use Craffft\CssStyleSelectorBundle\Repository\CssStyleRepository;

/**
 * Derives the selection of the CSS style selector field (cssStyleSelector)
 * from the CSS classes of a record, so that the classes of the demo elements
 * show up as selected styles in the back end instead of as free text in the
 * CSS class field.
 *
 * Uses the styles in the database (Creuva groups and own groups); a selected
 * style is stored by its CSS classes.
 */
class CssStyleValues
{
    public const FIELD = 'cssStyleSelector';

    /**
     * Table → style type.
     */
    private const TABLES = [
        'tl_content' => CssStyleSelectorModel::TYPE_CONTENT,
        'tl_article' => CssStyleSelectorModel::TYPE_ARTICLE,
        'tl_module' => CssStyleSelectorModel::TYPE_MODULE,
    ];

    public function __construct(private readonly CssStyleRepository $repository)
    {
    }

    /**
     * Returns the serialized value of the cssStyleSelector field for the given
     * classes, or null if none of them belongs to a style.
     *
     * @param string      $table   tl_content, tl_article or tl_module
     * @param string|null $type    element type (content elements and modules)
     * @param string      $classes CSS classes separated by spaces
     */
    public function resolve(string $table, string|null $type, string $classes): string|null
    {
        if (!isset(self::TABLES[$table]) || '' === trim($classes)) {
            return null;
        }

        $tokens = CssStyleRepository::splitClasses($classes);
        $groups = $this->repository->findAvailableGroups(self::TABLES[$table], $type);
        $values = [];

        // Styles whose classes are all in the CSS class field
        foreach ($groups as $group) {
            foreach (array_keys($group['styles']) as $value) {
                if (!array_diff(CssStyleRepository::splitClasses((string) $value), $tokens)) {
                    $values[] = (string) $value;
                }
            }
        }

        // Single selection: the first matching style of a group wins
        $selected = CssStyleRepository::filterSelection($groups, $values);

        return $selected ? serialize($selected) : null;
    }
}
