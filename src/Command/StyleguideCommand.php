<?php

declare(strict_types=1);

/*
 * Creuva theme bundle for Contao Open Source CMS
 *
 * Copyright (C) pdir GmbH <develop@pdir.dev>
 */

namespace Pdir\CreuvaThemeBundle\Command;

use Pdir\CreuvaThemeBundle\Installer\DemoImporter;
use Pdir\CreuvaThemeBundle\Installer\StyleguideInstaller;
use Pdir\CreuvaThemeBundle\Installer\ThemeInstaller;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\NullOutput;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Question\ChoiceQuestion;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\DependencyInjection\Attribute\Autowire;

#[AsCommand(
    name: 'creuva:styleguide',
    description: 'Adds the Creuva styleguide (all elements, template variants, classes and modules) as a hidden page to a website.',
)]
class StyleguideCommand extends Command
{
    public function __construct(
        private readonly StyleguideInstaller $styleguideInstaller,
        private readonly ThemeInstaller $themeInstaller,
        #[Autowire(param: 'contao.web_dir')]
        private readonly string $webDir,
        #[Autowire(param: 'contao.upload_path')]
        private readonly string $uploadPath,
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->addOption('root', 'r', InputOption::VALUE_REQUIRED, 'ID of the website root (asked for if there are several)')
            ->addOption('replace', null, InputOption::VALUE_NONE, 'Replace an existing styleguide of the website')
            ->setHelp(<<<'HELP'
                The <info>%command.name%</info> command adds the page "Styleguide" (alias "styleguide",
                hidden in the navigation, not indexed) to a website that uses the Creuva layout. It shows
                all elements, template variants, CSS classes and modules of the theme, independent of the
                demo content. Its modules, form and news archive get the prefix "Styleguide – ".

                  <info>php %command.full_name% --root=1</info>

                Install it again after an update of the theme (the old styleguide is deleted first):

                  <info>php %command.full_name% --root=1 --replace</info>
                HELP)
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $rootPages = $this->themeInstaller->getRootPages();

        if (!$rootPages) {
            $io->error('There is no website root. Run "creuva:install" first.');

            return Command::FAILURE;
        }

        if (null !== $input->getOption('root')) {
            $rootId = (int) $input->getOption('root');
        } elseif (1 === \count($rootPages) || !$input->isInteractive()) {
            $rootId = (int) array_key_first($rootPages);
        } else {
            $choices = [];

            foreach ($rootPages as $page) {
                $choices[(string) $page['id']] = \sprintf('%s (ID %d%s)', $page['title'], $page['id'], $page['dns'] ? ', '.$page['dns'] : '');
            }

            $rootId = (int) $io->askQuestion(new ChoiceQuestion('To which website should the styleguide be added?', $choices, (string) array_key_first($rootPages)));
        }

        if (!isset($rootPages[$rootId])) {
            $io->error(\sprintf('There is no website root with ID %d.', $rootId));

            return Command::FAILURE;
        }

        $themeId = $this->styleguideInstaller->findThemeId($rootId);

        if (null === $themeId) {
            $io->error(\sprintf('The website root %d has no page layout. Run "creuva:install --root=%d" first.', $rootId, $rootId));

            return Command::FAILURE;
        }

        if (null !== ($pageId = $this->styleguideInstaller->findPage($rootId))) {
            if (!$input->getOption('replace')) {
                $io->error(\sprintf('The website already has a styleguide (page ID %d). Use --replace to install it again.', $pageId));

                return Command::FAILURE;
            }

            $this->styleguideInstaller->remove($pageId, $themeId);
            $io->note(\sprintf('The existing styleguide (page ID %d) has been deleted.', $pageId));
        }

        $result = $this->styleguideInstaller->install($rootId, $themeId, $rootPages[$rootId]['language'] ?: 'de');

        // Keep links in navigation modules (e.g. the footer)
        if (null !== $pageId) {
            $this->styleguideInstaller->relinkPage($pageId, $result['pageId']);
        }

        // The files are in a public folder that needs a symlink in the web directory
        // (only if it does not exist yet, e.g. a junction on Windows would be removed)
        if (!file_exists($this->webDir.'/'.$this->uploadPath.'/'.DemoImporter::FILES_FOLDER) && $this->getApplication()?->has('contao:symlinks')) {
            try {
                $this->getApplication()->find('contao:symlinks')->run(new ArrayInput([]), new NullOutput());
            } catch (\Throwable $e) {
                $result['warnings'][] = 'The symlinks could not be created ('.$e->getMessage().'). Run "contao:symlinks" manually, otherwise the SVG icons and downloads of the styleguide cannot be loaded.';
            }
        }

        foreach ($result['warnings'] as $warning) {
            $io->warning($warning);
        }

        $io->success(\sprintf('The styleguide has been added (page ID %d, alias "%s").', $result['pageId'], StyleguideInstaller::ALIAS));

        return Command::SUCCESS;
    }
}
