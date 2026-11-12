<?php

declare(strict_types=1);

/*
 * Creuva theme bundle for Contao Open Source CMS
 *
 * Copyright (C) pdir GmbH <develop@pdir.dev>
 */

namespace Pdir\CreuvaThemeBundle\Command;

use Pdir\CreuvaThemeBundle\Installer\CssStyleImporter;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'creuva:css-styles',
    description: 'Adds missing Creuva groups and styles to the CSS style selector.',
)]
class CssStyleCommand extends Command
{
    public function __construct(private readonly CssStyleImporter $importer)
    {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this->setHelp(<<<'HELP'
            The <info>%command.name%</info> command imports the Creuva groups of config/css-styles.yaml
            into the CSS style selector (Layout › CSS styles), e.g. after a theme update with new classes.

            Only missing groups and styles are added. Changes in the back end (titles, element
            types, removed styles, own groups) are kept.
            HELP);
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        if (!$this->importer->isAvailable()) {
            $io->error('The CSS style selector (pdir/css-style-selector-bundle) is not installed or its tables do not exist yet. Run "contao:migrate" first.');

            return Command::FAILURE;
        }

        $count = $this->importer->import();

        if (0 === array_sum($count)) {
            $io->success('The CSS style selector already contains all Creuva groups and styles.');
        } else {
            $io->success(\sprintf('Added %d group(s) and %d style(s) to the CSS style selector.', $count['groups'], $count['styles']));
        }

        return Command::SUCCESS;
    }
}
