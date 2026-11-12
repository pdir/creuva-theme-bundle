<?php

declare(strict_types=1);

/*
 * Creuva theme bundle for Contao Open Source CMS
 *
 * Copyright (C) pdir GmbH <develop@pdir.dev>
 */

namespace Pdir\CreuvaThemeBundle\Controller\Backend;

use Contao\CoreBundle\Controller\Backend\AbstractBackendController;
use Pdir\CreuvaThemeBundle\Exporter\ZipWriter;
use Pdir\CreuvaThemeBundle\Installer\ThemeInstaller;
use Symfony\Bundle\FrameworkBundle\Console\Application;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Output\BufferedOutput;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Finder\Finder;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\ResponseHeaderBag;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\HttpKernel\KernelInterface;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\Component\Yaml\Yaml;

/**
 * Back end page "Layout › Creuva": the same actions as the console commands
 * creuva:install, creuva:export, creuva:styleguide and creuva:css-styles. The
 * commands are run with the options of the forms, so both ways behave exactly
 * the same. A reset without a new installation is only available on the
 * console (creuva:reset). Exports stay in var/exports and can be downloaded as
 * a ZIP file.
 */
#[IsGranted('ROLE_ADMIN', message: 'Access restricted to administrators.')]
class InstallerController extends AbstractBackendController
{
    public const ROUTE = 'creuva_installer';

    public const DOWNLOAD_ROUTE = 'creuva_installer_download';

    private const RESULT_KEY = 'creuva_installer_result';

    private const NAME_PATTERN = '/^[A-Za-z0-9][A-Za-z0-9._-]*$/';

    public function __construct(
        private readonly ThemeInstaller $installer,
        private readonly KernelInterface $kernel,
        private readonly ZipWriter $zipWriter,
        #[Autowire(param: 'kernel.project_dir')]
        private readonly string $projectDir,
        private readonly Filesystem $filesystem = new Filesystem(),
    ) {
    }

    #[Route('%contao.backend.route_prefix%/creuva', name: self::ROUTE, defaults: ['_scope' => 'backend'], methods: ['GET', 'POST'])]
    public function __invoke(Request $request): Response
    {
        $session = $this->getBackendSessionBag();

        if ($request->isMethod('POST')) {
            $action = (string) $request->request->get('action');

            if ('export-delete' === $action) {
                $session?->set(self::RESULT_KEY, ['action' => 'export', ...$this->deleteExport((string) $request->request->get('name'))]);
            } elseif (null !== ($command = $this->buildCommand($request))) {
                $result = ['action' => $action, ...$this->run($command)];

                // Offer the new export for download right away
                if ('export' === $action && $result['success']) {
                    $result['export'] = $this->getExports()[0]['name'] ?? null;
                }

                $session?->set(self::RESULT_KEY, $result);
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
            'exports' => $this->getExports(),
        ]);
    }

    /**
     * Downloads an export of var/exports as a ZIP file (demo.yaml and the folder
     * "files"); the archive is created on the fly (ZipWriter, works without the
     * PHP extension "zip").
     */
    #[Route('%contao.backend.route_prefix%/creuva/export/{name}', name: self::DOWNLOAD_ROUTE, defaults: ['_scope' => 'backend'], methods: ['GET'])]
    public function download(string $name): Response
    {
        $dir = $this->getExportDir($name);

        if (null === $dir) {
            throw new NotFoundHttpException(\sprintf('The export "%s" does not exist.', $name));
        }

        @set_time_limit(0);

        // Next to the exports, the temporary directory of the system may not be writable
        $archive = $this->projectDir.'/var/exports/.'.$name.'-'.bin2hex(random_bytes(4)).'.zip';
        $this->zipWriter->create($dir, $archive);

        $response = new BinaryFileResponse($archive);
        $response->setContentDisposition(ResponseHeaderBag::DISPOSITION_ATTACHMENT, $name.'.zip');
        $response->deleteFileAfterSend();

        return $response;
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
            'export' => array_filter([
                'command' => 'creuva:export',
                'root' => (string) $post->getInt('root') ?: null,
                '--name' => trim((string) $post->get('name')) ?: null,
                '--title' => trim((string) $post->get('export_title')) ?: null,
                '--force' => $post->getBoolean('force'),
            ]),
            default => null,
        };
    }

    /**
     * Exports in var/exports, newest first.
     *
     * @return list<array{name: string, title: string, date: int, files: int, size: int}>
     */
    private function getExports(): array
    {
        $exports = [];

        foreach (glob($this->projectDir.'/var/exports/*/demo.yaml') ?: [] as $file) {
            $dir = \dirname($file);
            $name = basename($dir);

            if (!preg_match(self::NAME_PATTERN, $name)) {
                continue;
            }

            try {
                $title = (string) (Yaml::parseFile($file)['title'] ?? $name);
            } catch (\Throwable) {
                $title = $name;
            }

            $files = 0;
            $size = filesize($file) ?: 0;

            if (is_dir($dir.'/files')) {
                foreach ((new Finder())->files()->in($dir.'/files') as $item) {
                    ++$files;
                    $size += $item->getSize();
                }
            }

            $exports[] = ['name' => $name, 'title' => $title, 'date' => (int) filemtime($file), 'files' => $files, 'size' => $size];
        }

        usort($exports, static fn (array $a, array $b): int => $b['date'] <=> $a['date']);

        return $exports;
    }

    private function getExportDir(string $name): string|null
    {
        $dir = $this->projectDir.'/var/exports/'.$name;

        return preg_match(self::NAME_PATTERN, $name) && is_file($dir.'/demo.yaml') ? $dir : null;
    }

    /**
     * @return array{success: bool, command: string, output: string, message: string}
     */
    private function deleteExport(string $name): array
    {
        $dir = $this->getExportDir($name);

        if (null !== $dir) {
            $this->filesystem->remove($dir);
        }

        return [
            'success' => null !== $dir,
            'command' => '',
            'output' => '',
            'message' => null !== $dir ? 'export_deleted' : 'export_missing',
        ];
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
                $quoted = preg_match('/^[\w.\/:-]+$/', (string) $item) ? $item : "'".str_replace("'", "'\\''", (string) $item)."'";

                // Arguments (e.g. the root of creuva:export) without the name
                $parts[] = match (true) {
                    true === $item => $name,
                    !str_starts_with($name, '-') => $quoted,
                    default => $name.'='.$quoted,
                };
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
