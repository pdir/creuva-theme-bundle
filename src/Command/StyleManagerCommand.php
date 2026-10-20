<?php

declare(strict_types=1);

/*
 * Creuva theme bundle for Contao Open Source CMS
 *
 * Copyright (C) pdir GmbH <develop@pdir.de>
 */

namespace Pdir\CreuvaThemeBundle\Command;

use Pdir\CreuvaThemeBundle\Installer\StyleManagerImporter;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'creuva:style-manager',
    description: 'Adds missing Creuva archives, groups and classes to the Style Manager.',
)]
class StyleManagerCommand extends Command
{
    public function __construct(private readonly StyleManagerImporter $importer)
    {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this->setHelp(<<<'HELP'
            The <info>%command.name%</info> command imports the Creuva groups of config/style-manager.yaml
            into the Style Manager (Layout › Style Manager), e.g. after a theme update with new classes.

            Only missing archives, groups and classes are added. Changes in the back end (titles,
            element types, removed classes, own groups) are kept.
            HELP);
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        if (!$this->importer->isAvailable()) {
            $io->error('The Style Manager (oveleon/contao-component-style-manager) is not installed or its tables do not exist yet. Run "contao:migrate" first.');

            return Command::FAILURE;
        }

        $count = $this->importer->import();

        if (0 === array_sum($count)) {
            $io->success('The Style Manager already contains all Creuva groups and classes.');
        } else {
            $io->success(\sprintf('Added %d archive(s), %d group(s) and %d class(es) to the Style Manager.', $count['archives'], $count['groups'], $count['classes']));
        }

        return Command::SUCCESS;
    }
}
