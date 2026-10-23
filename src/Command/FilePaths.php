<?php

declare(strict_types=1);

namespace MarkupCarve\CakeCarve\Command;

use InvalidArgumentException;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use SplFileInfo;

final class FilePaths
{
    /**
     * @param array<string> $paths
     *
     * @throws \InvalidArgumentException
     *
     * @return array<string>
     */
    public static function expand(array $paths): array
    {
        $files = [];
        foreach ($paths as $path) {
            if (is_file($path) && is_readable($path)) {
                $files[] = $path;

                continue;
            }
            if (!is_dir($path) || !is_readable($path)) {
                throw new InvalidArgumentException('Unreadable Carve path: ' . $path);
            }
            $iterator = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($path));
            foreach ($iterator as $entry) {
                if ($entry instanceof SplFileInfo && $entry->isFile() && $entry->getExtension() === 'crv') {
                    $files[] = $entry->getPathname();
                }
            }
        }
        sort($files);

        return $files;
    }
}
