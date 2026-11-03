<?php

declare(strict_types=1);

/*
 * Creuva theme bundle for Contao Open Source CMS
 *
 * Copyright (C) pdir GmbH <develop@pdir.de>
 */

namespace Pdir\CreuvaThemeBundle\Command;

use Pdir\CreuvaThemeBundle\Design\DesignSettings;
use Pdir\CreuvaThemeBundle\Installer\CachePurger;
use Pdir\CreuvaThemeBundle\Installer\DemoImporter;
use Pdir\CreuvaThemeBundle\Installer\InstallOptions;
use Pdir\CreuvaThemeBundle\Installer\SiteReplacer;
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
use Symfony\Component\Yaml\Exception\ParseException;

#[AsCommand(
    name: 'creuva:install',
    description: 'Creates the Creuva theme with front end modules and page layout and assigns it to a website root.',
)]
class InstallCommand extends Command
{
    public function __construct(
        private readonly CachePurger $cachePurger,
        private readonly ThemeInstaller $installer,
        private readonly DesignSettings $designSettings,
        private readonly SiteReplacer $siteReplacer,
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
            ->addOption('theme-name', null, InputOption::VALUE_REQUIRED, 'Name of the theme', 'Creuva')
            ->addOption('variant', null, InputOption::VALUE_REQUIRED, 'Color mode ('.implode(', ', DesignSettings::VARIANTS).')', 'light')
            ->addOption('typography', null, InputOption::VALUE_REQUIRED, 'Typography ('.implode(', ', DesignSettings::TYPOGRAPHIES).')', 'modern')
            ->addOption('scheme', null, InputOption::VALUE_REQUIRED, 'Color scheme ('.implode(', ', $this->designSettings->getSchemeNames()).')', $this->designSettings->getDefaultScheme())
            ->addOption('root', 'r', InputOption::VALUE_REQUIRED | InputOption::VALUE_IS_ARRAY, 'ID of an existing website root that should use the Creuva layout (multiple allowed)')
            ->addOption('with-pages', null, InputOption::VALUE_NONE, 'Create a new website root with start, contact, imprint, privacy, sitemap and search pages')
            ->addOption('demo', null, InputOption::VALUE_OPTIONAL, 'Create a new website root with the demo content: name of a demo of the bundle (e.g. "standard") or a folder with a demo.yaml file (e.g. "files/my-demo")', false)
            ->addOption('site-title', null, InputOption::VALUE_REQUIRED, 'Title of the new website root (with --with-pages)', 'Creuva')
            ->addOption('language', null, InputOption::VALUE_REQUIRED, 'Language of the new website root (with --with-pages)', 'de')
            ->addOption('dns', null, InputOption::VALUE_REQUIRED, 'Domain of the new website root (with --with-pages)', '')
            ->addOption('reset', null, InputOption::VALUE_NONE, 'Delete all existing contents first (see creuva:reset), e.g. to import the demo again')
            ->addOption('replace', null, InputOption::VALUE_NONE, 'Delete the website with the same domain and language (--dns, --language) and the theme named --theme-name first, e.g. to import a demo again in a multisite installation')
            ->addOption('force', 'f', InputOption::VALUE_NONE, 'Delete without asking for confirmation (with --reset or --replace)')
            ->addOption('no-backup', null, InputOption::VALUE_NONE, 'Do not create a database backup before deleting (with --reset or --replace)')
            ->setHelp(<<<'HELP'
                The <info>%command.name%</info> command sets up the Creuva theme:

                  * theme "Creuva" with main navigation, header, brand, footer navigations and copyright modules
                    and an image size for the logo
                  * page layout of type "modern" with the template page/layout/creuva and all modules assigned to their slots
                  * the layout for the selected website roots

                Assign the theme to an existing website:

                  <info>php %command.full_name% --root=1</info>

                Fresh installation with a new site structure:

                  <info>php %command.full_name% --with-pages --site-title="My company"</info>

                Complete demo website with sample pages, news, images and forms:

                  <info>php %command.full_name% --demo</info>

                Own demo from a folder in the file manager (same structure as the demos of the bundle:
                demo.yaml and an optional "files" folder with images and downloads):

                  <info>php %command.full_name% --demo=files/my-demo</info>

                Delete all existing contents first and import the demo again (a database backup is created):

                  <info>php %command.full_name% --demo --reset</info>

                Import a demo again in a multisite installation: only the website with the same domain and language,
                its theme and the forms, news archives and calendars that no other website uses are deleted
                (a database backup is created):

                  <info>php %command.full_name% --demo=files/my-demo --dns=example.com --theme-name="Example" --replace</info>

                Contact, imprint, privacy, sitemap and search pages of an existing website are detected by their alias
                (e.g. kontakt, impressum, datenschutz, suche) and linked in the header and footer.
                HELP)
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $io->title('Creuva – create something new');

        if ($missing = $this->installer->getMissingColumns()) {
            $io->error([
                'The database is not up to date (missing: '.implode(', ', $missing).').',
                'Please run "contao:migrate" first.',
            ]);

            return Command::FAILURE;
        }

        $themeName = trim((string) $input->getOption('theme-name'));

        if ('' === $themeName) {
            $io->error('The theme name must not be empty.');

            return Command::INVALID;
        }

        $variant = (string) $input->getOption('variant');
        $typography = (string) $input->getOption('typography');

        // The former variant "editorial" is a typography now (combinable with every color mode)
        if ('editorial' === $variant) {
            [$variant, $typography] = ['light', 'editorial'];
        }

        if (!\in_array($variant, DesignSettings::VARIANTS, true)) {
            $io->error(\sprintf('Invalid variant "%s" (allowed: %s).', $variant, implode(', ', DesignSettings::VARIANTS)));

            return Command::INVALID;
        }

        if (!\in_array($typography, DesignSettings::TYPOGRAPHIES, true)) {
            $io->error(\sprintf('Invalid typography "%s" (allowed: %s).', $typography, implode(', ', DesignSettings::TYPOGRAPHIES)));

            return Command::INVALID;
        }

        $scheme = (string) $input->getOption('scheme');

        if (!$this->designSettings->hasScheme($scheme)) {
            $io->error(\sprintf('Invalid color scheme "%s" (allowed: %s).', $scheme, implode(', ', $this->designSettings->getSchemeNames())));

            return Command::INVALID;
        }

        $rootPages = $this->installer->getRootPages();
        $createPages = (bool) $input->getOption('with-pages');
        $rootIds = array_map('intval', (array) $input->getOption('root'));

        $demo = $input->getOption('demo');

        if (false !== $demo) {
            $demo ??= 'standard';

            try {
                $this->installer->checkDemo($demo);
            } catch (\InvalidArgumentException|ParseException $e) {
                $io->error($e->getMessage());

                return Command::INVALID;
            }

            $createPages = true;
        } else {
            $demo = null;
        }

        if ($input->getOption('reset')) {
            if ($rootIds) {
                $io->error('The options --reset and --root cannot be combined.');

                return Command::INVALID;
            }

            $reset = new ArrayInput(array_filter([
                '--force' => $input->getOption('force'),
                '--no-backup' => $input->getOption('no-backup'),
            ]));
            $reset->setInteractive($input->isInteractive());

            if (Command::SUCCESS !== $this->getApplication()?->find('creuva:reset')->run($reset, $output)) {
                return Command::FAILURE;
            }

            $rootPages = $this->installer->getRootPages();
        }

        if ($input->getOption('replace')) {
            if (!$createPages) {
                $io->error('The option --replace requires --demo or --with-pages.');

                return Command::INVALID;
            }

            if ($input->getOption('reset') || $rootIds) {
                $io->error('The option --replace cannot be combined with --reset or --root.');

                return Command::INVALID;
            }

            if (Command::SUCCESS !== $this->replaceSite($input, $output, $io, $themeName)) {
                return Command::FAILURE;
            }

            $rootPages = $this->installer->getRootPages();
        }

        if ($this->installer->themeExists($themeName)) {
            $io->error(\sprintf('A theme named "%s" already exists. Use --theme-name to choose a different name.', $themeName));

            return Command::FAILURE;
        }

        if ($createPages && $rootIds) {
            $io->error('The options --with-pages/--demo and --root cannot be combined.');

            return Command::INVALID;
        }

        foreach ($rootIds as $rootId) {
            if (!isset($rootPages[$rootId])) {
                $io->error(\sprintf('There is no website root with ID %d.', $rootId));

                return Command::FAILURE;
            }
        }

        if (!$createPages && !$rootIds) {
            if (!$rootPages) {
                // Fresh installation: create a site structure so the theme works right away
                $io->note('No website root found – a new site structure will be created.');
                $createPages = true;
            } elseif ($input->isInteractive()) {
                $rootIds = $this->askForRootPages($io, $rootPages);
            }
        }

        $language = (string) $input->getOption('language');

        // Module names and texts follow the language of an existing website root
        if (!$createPages && $rootIds && !$input->hasParameterOption('--language')) {
            $language = $rootPages[$rootIds[0]]['language'] ?: $language;
        }

        if ($createPages && !preg_match('/^[a-z]{2}(-[A-Za-z]{2,4})?$/', $language)) {
            $io->error(\sprintf('Invalid language "%s".', $language));

            return Command::INVALID;
        }

        $dns = (string) $input->getOption('dns');

        if ($createPages) {
            foreach ($rootPages as $page) {
                if ($page['dns'] === $dns && $page['language'] === $language) {
                    $io->error(\sprintf('The website root "%s" (ID %d) already uses the domain "%s" and the language "%s". Use --dns or --language to create a separate website.', $page['title'], $page['id'], $dns ?: '*', $language));

                    return Command::FAILURE;
                }
            }
        }

        $result = $this->installer->install(new InstallOptions(
            themeName: $themeName,
            variant: $variant,
            typography: $typography,
            scheme: $scheme,
            createPages: $createPages,
            siteTitle: (string) $input->getOption('site-title'),
            language: $language,
            dns: $dns,
            demo: $demo,
        ), $rootIds);

        // The demo files are in a public folder that needs a symlink in the web directory
        // (only if it does not exist yet: contao:symlinks removes all links first, and
        // without the right to create symlinks, e.g. on Windows, they would be gone)
        if ($demo && !file_exists($this->webDir.'/'.$this->uploadPath.'/'.DemoImporter::FILES_FOLDER) && $this->getApplication()?->has('contao:symlinks')) {
            $warning = 'The symlinks could not be created%s. Run "contao:symlinks" manually, otherwise the SVG icons and downloads of the demo cannot be loaded.';

            try {
                if (Command::SUCCESS !== $this->getApplication()->find('contao:symlinks')->run(new ArrayInput([]), new NullOutput())) {
                    $result->warnings[] = \sprintf($warning, '');
                }
            } catch (\Throwable $e) {
                $result->warnings[] = \sprintf($warning, ' ('.$e->getMessage().')');
            }
        }

        foreach ($result->warnings as $warning) {
            $io->warning($warning);
        }

        $io->section('Created');
        $io->listing([
            \sprintf('Theme "%s" (ID %d)', $themeName, $result->themeId),
            \sprintf('Page layout "page/layout/creuva" (ID %d)', $result->layoutId),
            ...array_map(
                static fn (int $id, string $name): string => \sprintf('Module "%s" (ID %d)', $name, $id),
                array_keys($result->modules),
                $result->modules,
            ),
            \sprintf('%d image size(s) (the logo uses ID %d)', \count($result->imageSizes), $result->imageSizes['logo']),
            ...($result->pages ? [\sprintf('Website root with %d pages%s', \count($result->pages), $demo ? ' (demo "'.$demo.'")' : ' ('.implode(', ', array_keys($result->pages)).')')] : []),
        ]);

        if ($result->rootPages) {
            $io->success(\sprintf('The Creuva layout is now used by the website root(s) %s.', implode(', ', $result->rootPages)));
        } else {
            $io->success('The Creuva theme has been created.');
            $io->note('Assign the layout "page/layout/creuva" to a website root in the site structure, or run the command again with --root=<ID>.');
        }

        $io->text([
            'Next steps:',
            \sprintf(' * Add your logo in the header module (ID %d) and the brand module (ID %d)', $result->moduleIds['header'], $result->moduleIds['brand']),
            ...($demo ? [] : [\sprintf(' * Add the social media links in the brand module (ID %d)', $result->moduleIds['brand'])]),
            \sprintf(' * Choose the color scheme, color mode, typography and colors in the page layout (ID %d) under "Creuva design"', $result->layoutId),
        ]);

        return Command::SUCCESS;
    }

    /**
     * Deletes the website with the domain and language of the new website root and the
     * theme named --theme-name (creuva:install --replace).
     */
    private function replaceSite(InputInterface $input, OutputInterface $output, SymfonyStyle $io, string $themeName): int
    {
        $dns = (string) $input->getOption('dns');
        $language = (string) $input->getOption('language');

        $plan = $this->siteReplacer->plan($this->siteReplacer->findRootPages($dns, $language), $themeName);

        if ($plan->errors) {
            $io->error(['Nothing has been deleted.', ...$plan->errors]);

            return Command::FAILURE;
        }

        if ($plan->isEmpty()) {
            $io->note(\sprintf('There is no website for the domain "%s" and the language "%s" and no theme named "%s" – nothing to replace.', $dns ?: '*', $language, $themeName));

            return Command::SUCCESS;
        }

        $io->section('Replace');
        $io->text('The following records will be deleted:');
        $io->listing([
            ...array_map(static fn (int $id, string $title): string => \sprintf('Website root "%s" (ID %d)', $title, $id), array_keys($plan->rootPages), $plan->rootPages),
            ...array_map(static fn (int $id, string $name): string => \sprintf('Theme "%s" (ID %d)', $name, $id), array_keys($plan->themes), $plan->themes),
            ...array_map(
                static fn (string $table, int $rows): string => \sprintf('%s: %d %s', $table, $rows, 1 === $rows ? 'row' : 'rows'),
                array_keys($plan->getSummary()),
                $plan->getSummary(),
            ),
        ]);

        foreach ($plan->warnings as $warning) {
            $io->warning($warning);
        }

        $io->text('Other websites, users, members and the files in the file manager are kept (demo files are overwritten).');

        if (!$input->getOption('force')) {
            if (!$input->isInteractive()) {
                $io->error('Use --force to replace the website without confirmation.');

                return Command::FAILURE;
            }

            if (!$io->confirm('Do you really want to delete these records?', false)) {
                $io->note('Nothing has been changed.');

                return Command::FAILURE;
            }
        }

        if (!$input->getOption('no-backup') && Command::SUCCESS !== $this->createBackup($io)) {
            return Command::FAILURE;
        }

        $this->siteReplacer->remove($plan);

        // Cached pages and generated images of the old website
        if ($this->getApplication()?->has('contao:automator')) {
            foreach ($this->cachePurger->getAutomatorTasks() as $task) {
                $this->getApplication()->find('contao:automator')->run(new ArrayInput(['task' => $task]), new NullOutput());
            }
        }

        $io->text(\sprintf('%d records have been deleted.', array_sum($plan->getSummary())));
        $io->newLine();

        return Command::SUCCESS;
    }

    private function createBackup(SymfonyStyle $io): int
    {
        $application = $this->getApplication();

        if (!$application?->has('contao:backup:create')) {
            $io->error('The backup could not be created (command "contao:backup:create" not found). Use --no-backup to replace the website anyway.');

            return Command::FAILURE;
        }

        $backup = new ArrayInput(['name' => 'creuva-replace__'.date('YmdHis').'.sql.gz']);
        $backup->setInteractive(false);

        if (Command::SUCCESS !== $application->find('contao:backup:create')->run($backup, new NullOutput())) {
            $io->error('The backup could not be created. Nothing has been changed.');

            return Command::FAILURE;
        }

        $io->text(\sprintf('Backup created: var/backups/%s', $backup->getArgument('name')));

        return Command::SUCCESS;
    }

    /**
     * @param array<int, array{id: int, title: string, dns: string, language: string, layout: int}> $rootPages
     *
     * @return list<int>
     */
    private function askForRootPages(SymfonyStyle $io, array $rootPages): array
    {
        $choices = ['none' => 'Do not assign the layout yet'];

        foreach ($rootPages as $page) {
            $choices[(string) $page['id']] = \sprintf(
                '%s (ID %d%s, %s)%s',
                $page['title'],
                $page['id'],
                $page['dns'] ? ', '.$page['dns'] : '',
                $page['language'],
                $page['layout'] ? ' – replaces current layout' : '',
            );
        }

        $question = new ChoiceQuestion('Which website roots should use the Creuva layout? (comma separated)', $choices, \count($rootPages) > 1 ? 'none' : (string) array_key_first($rootPages));
        $question->setMultiselect(true);

        $answers = (array) $io->askQuestion($question);

        return array_values(array_map('intval', array_filter($answers, static fn ($answer) => 'none' !== $answer)));
    }
}
