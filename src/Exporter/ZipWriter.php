<?php

declare(strict_types=1);

/*
 * Creuva theme bundle for Contao Open Source CMS
 *
 * Copyright (C) pdir GmbH <develop@pdir.de>
 */

namespace Pdir\CreuvaThemeBundle\Exporter;

use Symfony\Component\Finder\Finder;

/**
 * Packs a folder into a ZIP file, e.g. an export of var/exports for the
 * download in the back end. Uses ZipArchive if the PHP extension "zip" is
 * installed, otherwise writes the ZIP format itself (PharData does not work
 * in Contao, which disables the phar stream wrapper). Files are compressed
 * with deflate if zlib is available; no ZIP64, so up to 4 GB.
 */
class ZipWriter
{
    public function create(string $dir, string $target): void
    {
        $files = [];

        foreach ((new Finder())->files()->in($dir)->ignoreDotFiles(false)->sortByName() as $file) {
            $files[str_replace('\\', '/', $file->getRelativePathname())] = $file->getPathname();
        }

        if (class_exists(\ZipArchive::class)) {
            $zip = new \ZipArchive();

            if (true !== $zip->open($target, \ZipArchive::CREATE | \ZipArchive::OVERWRITE)) {
                throw new \RuntimeException(\sprintf('Could not create the file "%s".', $target));
            }

            foreach ($files as $name => $path) {
                $zip->addFile($path, $name);
            }

            $zip->close();

            return;
        }

        $this->write($files, $target);
    }

    /**
     * @param array<string, string> $files name in the archive => path
     */
    private function write(array $files, string $target): void
    {
        $handle = fopen($target, 'w');

        if (false === $handle) {
            throw new \RuntimeException(\sprintf('Could not create the file "%s".', $target));
        }

        $central = '';
        $offset = 0;
        $deflate = \function_exists('gzdeflate');

        foreach ($files as $name => $path) {
            $data = (string) file_get_contents($path);
            $crc = crc32($data);
            $size = \strlen($data);
            $compressed = $deflate ? (string) gzdeflate($data, 6) : $data;
            $method = $deflate ? 8 : 0;
            [$time, $date] = $this->dosTime((int) filemtime($path));

            // Bit 11: file names are UTF-8
            $header = pack('vvvvvVVVvv', 20, 0x0800, $method, $time, $date, $crc, \strlen($compressed), $size, \strlen($name), 0);

            fwrite($handle, "PK\x03\x04".$header.$name.$compressed);

            // Comment length, disk, internal and external attributes, offset of the local header
            $central .= "PK\x01\x02".pack('v', 20).$header.pack('vvvVV', 0, 0, 0, 0, $offset).$name;
            $offset += 30 + \strlen($name) + \strlen($compressed);
        }

        fwrite($handle, $central."PK\x05\x06".pack('vvvvVVv', 0, 0, \count($files), \count($files), \strlen($central), $offset, 0));
        fclose($handle);
    }

    /**
     * @return array{int, int} time and date in MS-DOS format
     */
    private function dosTime(int $timestamp): array
    {
        $d = getdate(max($timestamp, 315532800));

        return [
            ($d['hours'] << 11) | ($d['minutes'] << 5) | intdiv($d['seconds'], 2),
            (($d['year'] - 1980) << 9) | ($d['mon'] << 5) | $d['mday'],
        ];
    }
}
