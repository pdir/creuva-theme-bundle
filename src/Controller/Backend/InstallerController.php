<?php

declare(strict_types=1);

/*
 * Creuva theme bundle for Contao Open Source CMS
 *
 * Copyright (C) pdir GmbH <develop@pdir.de>
 */

namespace Pdir\CreuvaThemeBundle\Controller\Backend;

use Contao\CoreBundle\Controller\Backend\AbstractBackendController;
use Pdir\CreuvaThemeBundle\Installer\ThemeInstaller;
use Symfony\Bundle\FrameworkBundle\Console\Application;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Output\BufferedOutput;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\KernelInterface;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

/**
 * Back end page "Layout › Creuva": the same actions as the console commands
 * creuva:install, creuva:styleguide and creuva:css-styles. The commands are run
 * with the options of the forms, so both ways behave exactly the same. A reset
 * without a new installation is only available on the console (creuva:reset).
 */
#[IsGranted('ROLE_ADMIN', message: 'Access restricted to administrators.')]
#[Route('%contao.backend.route_prefix%/creuva', name: self::ROUTE, defaults: ['_scope' => 'backend'], methods: ['GET', 'POST'])]
class InstallerController extends AbstractBackendController
{
    public const ROUTE = 'creuva_installer';

    private const RESULT_KEY = 'creuva_installer_result';

    public function __construct(
        private readonly ThemeInstaller $installer,
        private readonly KernelInterface $kernel,
    ) {
    }

    public function __invoke(Request $request): Response
    {
        $session = $this->getBackendSessionBag();

        if ($request->isMethod('POST')) {
            if (null !== ($command = $this->buildCommand($request))) {
                $session?->set(self::RESULT_KEY, ['action' => $request->request->get('action'), ...$this->run($command)]);
            }

            return $this->redirectToRoute(self::ROUTE, [], Response::HTTP_SEE_OTHER);
        }

        $result = $session?->get(self::RESULT_KEY);
        $session?->remove(self::RESULT_KEY);

        $rootPages = $this->installer->getRootPages();

        return $this->render('@Contao/backend/creuva_installer.html.twig', [
            'title' => 'Creuva',
            'headline' => 'Creuva',
            'result' => $result,
            'missing_columns' => $this->installer->getMissingColumns(),
            'demos' => $this->installer->getAvailableDemos(),
            'root_pages' => $rootPages,
            'theme_name' => $this->suggestThemeName(),
        ]);
    }

    /**
     * Translates the submitted form into the arguments of a console command.
     *
     * @return array<string, mixed>|null
     */
    private function buildCommand(Request $request): array|null
    {
        $post = $request->request;

        return match ($post->get('action')) {
            'install' => $this->buildInstallCommand($request),
            'styleguide' => array_filter([
                'command' => 'creuva:styleguide',
                '--root' => $post->getInt('root') ?: null,
                '--replace' => $post->getBoolean('replace'),
            ]),
            'css-styles' => ['command' => 'creuva:css-styles'],
            default => null,
        };
    }

    /**
     * @return array<string, mixed>
     */
    private function buildInstallCommand(Request $request): array
    {
        $post = $request->request;
        $mode = (string) $post->get('mode');

        $command = [
            'command' => 'creuva:install',
            '--theme-name' => trim((string) $post->get('theme_name')),
        ];

        if ('demo' === $mode) {
            $folder = trim((string) $post->get('demo_folder'), " /\\");
            $command['--demo'] = '' !== $folder ? $folder : (string) $post->get('demo');
        } elseif ('pages' === $mode) {
            $command['--with-pages'] = true;
            $command['--site-title'] = trim((string) $post->get('site_title')) ?: 'Creuva';
        } elseif ('roots' === $mode) {
            $command['--root'] = array_values(array_filter(array_map('intval', (array) $post->all('roots'))));
        }

        if ('demo' === $mode || 'pages' === $mode) {
            $command['--language'] = trim((string) $post->get('language')) ?: 'de';
            $command['--dns'] = trim((string) $post->get('dns'));

            if ($post->getBoolean('reset')) {
                $command['--reset'] = true;
                $command['--force'] = true;
                $command['--no-backup'] = $post->getBoolean('no_backup');
            }
        }

        return array_filter($command, static fn ($value) => !\in_array($value, [false, '', []], true));
    }

    /**
     * @param array<string, mixed> $command
     *
     * @return array{success: bool, command: string, output: string}
     */
    private function run(array $command): array
    {
        // Importing a demo copies files and creates a backup, which can take a while
        @set_time_limit(0);

        $application = new Application($this->kernel);
        $application->setAutoExit(false);

        $input = new ArrayInput($command);
        $input->setInteractive(false);

        $output = new BufferedOutput();

        try {
            $success = 0 === $application->run($input, $output);
        } catch (\Throwable $e) {
            $output->writeln($e->getMessage());
            $success = false;
        }

        return [
            'success' => $success,
            'command' => $this->toCommandLine($command),
            'output' => trim($output->fetch()),
        ];
    }

    /**
     * The command for the console, e.g. to repeat the action on another server.
     *
     * @param array<string, mixed> $command
     */
    private function toCommandLine(array $command): string
    {
        $parts = ['vendor/bin/contao-console', $command['command']];

        foreach ($command as $name => $value) {
            if ('command' === $name) {
                continue;
            }

            foreach ((array) $value as $item) {
                $parts[] = true === $item ? $name : $name.'='.(preg_match('/^[\w.\/:-]+$/', (string) $item) ? $item : "'".str_replace("'", "'\\''", (string) $item)."'");
            }
        }

        return implode(' ', $parts);
    }

    /**
     * "Creuva" or the next free name ("Creuva 2", …), because the command does
     * not overwrite an existing theme.
     */
    private function suggestThemeName(): string
    {
        $name = 'Creuva';

        for ($i = 2; $this->installer->themeExists($name); ++$i) {
            $name = 'Creuva '.$i;
        }

        return $name;
    }
}
