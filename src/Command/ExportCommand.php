<?php

declare(strict_types=1);

/*
 * Creuva theme bundle for Contao Open Source CMS
 *
 * Copyright (C) pdir GmbH <develop@pdir.dev>
 */

namespace Pdir\CreuvaThemeBundle\Command;

use Pdir\CreuvaThemeBundle\Exporter\DemoExporter;
use Pdir\CreuvaThemeBundle\Installer\ThemeInstaller;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\Filesystem\Filesystem;

#[AsCommand(
    name: 'creuva:export',
    description: 'Exports the contents of a website as a demo template to var/exports.',
)]
class ExportCommand extends Command
{
    public function __construct(
        private readonly DemoExporter $exporter,
        private readonly ThemeInstaller $installer,
        #[Autowire(param: 'kernel.project_dir')]
        private readonly string $projectDir,
        private readonly Filesystem $filesystem = new Filesystem(),
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->addArgument('root', InputArgument::OPTIONAL, 'ID of the website root (asked for if there are several)')
            ->addOption('name', null, InputOption::VALUE_REQUIRED, 'Folder name below var/exports (default: derived from the title of the website root)')
            ->addOption('title', null, InputOption::VALUE_REQUIRED, 'Title of the demo (default: title of the website root)')
            ->addOption('force', 'f', InputOption::VALUE_NONE, 'Overwrite an existing export with the same name')
            ->setHelp(<<<'HELP'
                The <info>%command.name%</info> command exports the page tree of a website with its articles,
                content elements, modules, forms, news, events, image sizes and files as a demo template:

                  <info>php %command.full_name% 1 --name=my-demo</info>

                The export is written to var/exports/<name>/ (demo.yaml and the folder "files"). IDs are
                replaced by references to aliases and file paths, so the demo can be imported into any
                installation. Copy the folder to files/<name> and run:

                  <info>php bin/console creuva:install --demo=files/<name></info>

                Header button, brand and footer columns are taken from the Creuva modules of the page
                layout, the design (color scheme, variant, corner radius) from the page layout and the
                website root. The image sizes of the theme and all image sizes used by the contents are
                exported with their media queries. Recipient addresses of forms are not exported.
                HELP)
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $io->title('Creuva – export a demo template');

        $roots = $this->installer->getRootPages();

        if (!$roots) {
            $io->error('There is no website root to export.');

            return Command::FAILURE;
        }

        $rootId = $input->getArgument('root');

        if (null === $rootId) {
            if (1 === \count($roots)) {
                $rootId = array_key_first($roots);
            } elseif ($input->isInteractive()) {
                $choices = array_map(static fn (array $root): string => \sprintf('%s (ID %d%s)', $root['title'], $root['id'], $root['dns'] ? ', '.$root['dns'] : ''), $roots);
                $choice = $io->choice('Which website do you want to export?', array_values($choices));
                $rootId = array_search($choice, $choices, true);
            } else {
                $io->error(\sprintf('There are several website roots, please pass the ID (%s).', implode(', ', array_keys($roots))));

                return Command::FAILURE;
            }
        }

        $root = $roots[(int) $rootId] ?? null;

        if (null === $root || !ctype_digit((string) $rootId)) {
            $io->error(\sprintf('The page ID "%s" is not a website root (available: %s).', $rootId, implode(', ', array_keys($roots))));

            return Command::FAILURE;
        }

        $name = (string) ($input->getOption('name') ?? $this->slug($root['title']));

        if (!preg_match('/^[A-Za-z0-9][A-Za-z0-9._-]*$/', $name)) {
            $io->error(\sprintf('The name "%s" is invalid (letters, digits, ".", "_" and "-" only).', $name));

            return Command::FAILURE;
        }

        $targetDir = $this->projectDir.'/var/exports/'.$name;

        if ($this->filesystem->exists($targetDir)) {
            if (!$input->getOption('force')) {
                $io->error(\sprintf('The folder "var/exports/%s" already exists. Use --force to overwrite it or choose another --name.', $name));

                return Command::FAILURE;
            }

            $this->filesystem->remove($targetDir);
        }

        $counts = $this->exporter->export((int) $rootId, $targetDir, (string) ($input->getOption('title') ?? $root['title']));

        $io->definitionList(
            ['Website' => \sprintf('%s (ID %d)', $root['title'], $root['id'])],
            ['Pages' => $counts['pages'] ?? 0],
            ['Articles' => $counts['articles'] ?? 0],
            ['Content elements' => $counts['elements'] ?? 0],
            ['Modules' => $counts['modules'] ?? 0],
            ['Forms' => $counts['forms'] ?? 0],
            ['News' => $counts['news'] ?? 0],
            ['Events' => $counts['events'] ?? 0],
            ['Image sizes' => $counts['imageSizes'] ?? 0],
            ['Files' => $counts['files'] ?? 0],
        );

        if ($warnings = $this->exporter->getWarnings()) {
            $io->warning($warnings);
        }

        $io->success(\sprintf('The demo has been exported to var/exports/%s.', $name));

        $io->text([
            'Import it into another installation:',
            \sprintf('  1. Copy the folder to files/%s (do not make it public)', $name),
            \sprintf('  2. vendor/bin/contao-console creuva:install --demo=files/%s', $name),
        ]);

        return Command::SUCCESS;
    }

    private function slug(string $text): string
    {
        $text = strtr($text, ['ä' => 'ae', 'ö' => 'oe', 'ü' => 'ue', 'ß' => 'ss', 'Ä' => 'ae', 'Ö' => 'oe', 'Ü' => 'ue']);

        return strtolower(trim((string) preg_replace('/[^A-Za-z0-9]+/', '-', $text), '-')) ?: 'demo';
    }
}
