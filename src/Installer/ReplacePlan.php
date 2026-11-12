<?php

declare(strict_types=1);

/*
 * Creuva theme bundle for Contao Open Source CMS
 *
 * Copyright (C) pdir GmbH <develop@pdir.dev>
 */

namespace Pdir\CreuvaThemeBundle\Installer;

/**
 * Records that SiteReplacer deletes before a website is imported again
 * (creuva:install --replace).
 */
final class ReplacePlan
{
    /**
     * @var array<int, string> root page ID => title
     */
    public array $rootPages = [];

    /**
     * @var array<int, string> theme ID => name
     */
    public array $themes = [];

    /**
     * @var array<string, list<int>> table => IDs
     */
    public array $records = [];

    /**
     * Reasons why the replacement is not possible (nothing is deleted).
     *
     * @var list<string>
     */
    public array $errors = [];

    /**
     * Records that are kept because other websites use them.
     *
     * @var list<string>
     */
    public array $warnings = [];

    public function isEmpty(): bool
    {
        return [] === array_filter($this->records);
    }

    /**
     * @param list<int> $ids
     */
    public function add(string $table, array $ids): void
    {
        $this->records[$table] = array_values(array_unique([...($this->records[$table] ?? []), ...$ids]));
    }

    /**
     * @return list<int>
     */
    public function get(string $table): array
    {
        return $this->records[$table] ?? [];
    }

    /**
     * @return array<string, int> table => number of records
     */
    public function getSummary(): array
    {
        return array_filter(array_map(\count(...), $this->records));
    }
}
