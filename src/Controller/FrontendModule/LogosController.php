<?php

declare(strict_types=1);

/*
 * Creuva theme bundle for Contao Open Source CMS
 *
 * Copyright (C) pdir GmbH <develop@pdir.de>
 */

namespace Pdir\CreuvaThemeBundle\Controller\FrontendModule;

use Contao\CoreBundle\Controller\FrontendModule\AbstractFrontendModuleController;
use Contao\CoreBundle\DependencyInjection\Attribute\AsFrontendModule;
use Contao\CoreBundle\Image\Studio\Figure;
use Contao\CoreBundle\Image\Studio\Studio;
use Contao\CoreBundle\Twig\FragmentTemplate;
use Contao\FilesModel;
use Contao\ModuleModel;
use Contao\StringUtil;
use Pdir\CreuvaThemeBundle\Design\DesignSettings;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Logo bar (e.g. partners or sponsors) with the images of the file manager in
 * the selected order. The link of a logo comes from the metadata of the file.
 */
#[AsFrontendModule(type: 'creuva_logos', category: 'creuva', template: 'frontend_module/creuva_logos')]
class LogosController extends AbstractFrontendModuleController
{
    public const VARIANTS = ['surface', 'primary', 'muted'];

    public function __construct(private readonly Studio $studio)
    {
    }

    protected function getResponse(FragmentTemplate $template, ModuleModel $model, Request $request): Response
    {
        $logos = [];

        foreach ($this->getImages($model) as $file) {
            $figure = $this->studio
                ->createFigureBuilder()
                ->fromFilesModel($file)
                ->setSize($model->imgSize)
                ->buildIfResourceExists()
            ;

            if (null === $figure) {
                continue;
            }

            $logos[] = ['figure' => $figure, 'href' => $this->getLink($figure)];
        }

        if (!$logos) {
            return new Response();
        }

        $template->set('logos', $logos);
        $template->set('variant', \in_array($model->creuvaLogosVariant, self::VARIANTS, true) ? $model->creuvaLogosVariant : 'surface');
        $template->set('height', preg_match(DesignSettings::LOGO_HEIGHT_PATTERN, (string) $model->creuvaLogosHeight) ? (string) $model->creuvaLogosHeight : null);

        return $template->getResponse();
    }

    /**
     * Images of the selected files and folders, in the order of the selection.
     *
     * @return list<FilesModel>
     */
    private function getImages(ModuleModel $model): array
    {
        $uuids = StringUtil::deserialize($model->multiSRC, true);

        if (!$uuids || !$files = $this->getContaoAdapter(FilesModel::class)->findMultipleByUuids($uuids)) {
            return [];
        }

        $images = [];

        foreach ($files as $file) {
            if ('folder' === $file->type) {
                foreach ($this->getContaoAdapter(FilesModel::class)->findByPid($file->uuid, ['order' => 'name']) ?? [] as $child) {
                    if ('file' === $child->type) {
                        $images[] = $child;
                    }
                }

                continue;
            }

            $images[] = $file;
        }

        // Keep the order of the selection (findMultipleByUuids sorts by path)
        $position = array_flip(array_map(static fn ($uuid): string => (string) $uuid, $uuids));
        usort($images, static fn (FilesModel $a, FilesModel $b): int => ($position[$a->uuid] ?? $position[$a->pid] ?? 0) <=> ($position[$b->uuid] ?? $position[$b->pid] ?? 0));

        return $images;
    }

    private function getLink(Figure $figure): string|null
    {
        return $figure->hasMetadata() ? ($figure->getMetadata()->getUrl() ?: null) : null;
    }
}
