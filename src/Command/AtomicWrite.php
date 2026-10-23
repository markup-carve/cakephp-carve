<?php

declare(strict_types=1);

namespace MarkupCarve\CakeCarve\Command;

use RuntimeException;

final class AtomicWrite
{
    public static function write(string $path, string $contents): void
    {
        if (is_link($path)) {
            $resolved = realpath($path);
            if ($resolved === false) {
                throw new RuntimeException('Cannot resolve output file: ' . $path);
            }
            $path = $resolved;
        }
        if (is_file($path) && !is_writable($path)) {
            throw new RuntimeException('Output file is not writable: ' . $path);
        }
        $directory = realpath(dirname($path));
        if ($directory === false || !is_dir($directory) || !is_writable($directory)) {
            throw new RuntimeException('Cannot write output directory: ' . $path);
        }
        $temp = @tempnam($directory, '.carve-');
        if ($temp === false) {
            throw new RuntimeException('Cannot create temporary output file: ' . $path);
        }
        try {
            $written = @file_put_contents($temp, $contents);
            if ($written !== strlen($contents)) {
                throw new RuntimeException('Cannot write output file: ' . $path);
            }
            $mode = is_file($path) ? (fileperms($path) & 0777) : (0666 & ~umask());
            if (!chmod($temp, $mode)) {
                throw new RuntimeException('Cannot set output file permissions: ' . $path);
            }
            if (is_file($path) && !self::takeOwnership($temp, $path)) {
                // Replacing would hand the file to this user, writing in place could truncate it; refuse both.
                throw new RuntimeException('Cannot replace ' . $path . ' without changing its owner; run the command as its owner.');
            }
            if (!@rename($temp, $path)) {
                throw new RuntimeException('Cannot replace output file: ' . $path);
            }
        } finally {
            if (is_file($temp)) {
                unlink($temp);
            }
        }
    }

    private static function takeOwnership(string $temp, string $path): bool
    {
        $owner = fileowner($path);
        $group = filegroup($path);
        if ($owner === false || $group === false) {
            return false;
        }
        if (fileowner($temp) !== $owner && !@chown($temp, $owner)) {
            return false;
        }

        return filegroup($temp) === $group || @chgrp($temp, $group);
    }
}
