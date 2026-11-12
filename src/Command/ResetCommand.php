<?php

declare(strict_types=1);

/*
 * Creuva theme bundle for Contao Open Source CMS
 *
 * Copyright (C) pdir GmbH <develop@pdir.dev>
 */

namespace Pdir\CreuvaThemeBundle\Command;

use Pdir\CreuvaThemeBundle\Installer\CachePurger;
use Pdir\CreuvaThemeBundle\Installer\ContentReset;
use Pdir\CreuvaThemeBundle\Installer\DemoImporter;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\NullOutput;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'creuva:reset',
    description: 'Deletes all contents (site structure, articles, themes, modules, forms, news …) as after a fresh installation.',
)]
class ResetCommand extends Command
{
    public function __construct(
        private readonly ContentReset $contentReset,
        private readonly CachePurger $cachePurger,
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->addOption('force', 'f', InputOption::VALUE_NONE, 'Reset without asking for confirmation (required with --no-interaction)')
            ->addOption('no-backup', null, InputOption::VALUE_NONE, 'Do not create a database backup before the reset')
            ->setHelp(<<<'HELP'
                The <info>%command.name%</info> command resets the contents of the installation to the state
                after a fresh installation. All Contao tables (tl_*) are emptied and their IDs start at 1 again.

                Kept are the back end users and groups, members and member groups, the file system table
                (tl_files) and technical tables (sessions, cron jobs, message queue). The files in the "files"
                directory are kept as well, only the Creuva demo folder is removed.

                A database backup is created first (restore with <info>contao:backup:restore</info>):

                  <info>php %command.full_name%</info>

                Reset and import the demo website in one step:

                  <info>php bin/console creuva:install --demo --reset</info>
                HELP)
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $io->title('Creuva – reset contents');

        $tables = array_filter($this->contentReset->getTables());
        $demoFiles = $this->contentReset->hasDemoFiles();

        if (!$tables && !$demoFiles) {
            $io->success('There are no contents to delete.');

            return Command::SUCCESS;
        }

        $io->text('The following tables will be emptied:');
        $io->listing(array_map(
            static fn (string $table, int $rows): string => \sprintf('%s (%d %s)', $table, $rows, 1 === $rows ? 'row' : 'rows'),
            array_keys($tables),
            $tables,
        ));

        if ($demoFiles) {
            $io->text(\sprintf('The folder "files/%s" will be deleted.', DemoImporter::FILES_FOLDER));
        }

        $io->newLine();
        $io->text(\sprintf('Kept tables: %s', implode(', ', ContentReset::KEEP_TABLES)));
        $io->text(\sprintf('All files outside "files/%s" are kept.', DemoImporter::FILES_FOLDER));

        if (!$input->getOption('force')) {
            if (!$input->isInteractive()) {
                $io->error('Use --force to reset the contents without confirmation.');

                return Command::FAILURE;
            }

            if (!$io->confirm('Do you really want to delete these contents?', false)) {
                $io->note('Nothing has been changed.');

                return Command::FAILURE;
            }
        }

        if (!$input->getOption('no-backup')) {
            $application = $this->getApplication();

            if (!$application?->has('contao:backup:create')) {
                $io->error('The backup could not be created (command "contao:backup:create" not found). Use --no-backup to reset anyway.');

                return Command::FAILURE;
            }

            $backup = new ArrayInput(['name' => 'creuva-reset__'.date('YmdHis').'.sql.gz']);
            $backup->setInteractive(false);

            if (Command::SUCCESS !== $application->find('contao:backup:create')->run($backup, new NullOutput())) {
                $io->error('The backup could not be created. Nothing has been changed.');

                return Command::FAILURE;
            }

            $io->text(\sprintf('Backup created: var/backups/%s', $backup->getArgument('name')));
        }

        $emptied = $this->contentReset->reset();

        // Cached pages and generated images of the old contents
        if ($this->getApplication()?->has('contao:automator')) {
            foreach ($this->cachePurger->getAutomatorTasks() as $task) {
                $this->getApplication()->find('contao:automator')->run(new ArrayInput(['task' => $task]), new NullOutput());
            }
        }

        $io->success(\sprintf('%d tables have been emptied%s.', \count($emptied), $demoFiles ? ' and the demo files have been deleted' : ''));

        return Command::SUCCESS;
    }
}
