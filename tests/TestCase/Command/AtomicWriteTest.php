<?php

declare(strict_types=1);

namespace MarkupCarve\CakeCarve\Test\TestCase\Command;

use MarkupCarve\CakeCarve\Command\AtomicWrite;
use PHPUnit\Framework\TestCase;
use RuntimeException;

class AtomicWriteTest extends TestCase
{
    public function testReplacesExistingFile(): void
    {
        $path = tempnam(sys_get_temp_dir(), 'carve-write-');
        self::assertIsString($path);
        try {
            file_put_contents($path, 'before');
            chmod($path, 0644);
            AtomicWrite::write($path, 'after');
            self::assertSame('after', file_get_contents($path));
            self::assertSame(0644, fileperms($path) & 0777);
        } finally {
            unlink($path);
        }
    }

    public function testFailureLeavesExistingFileUntouched(): void
    {
        $root = sys_get_temp_dir() . '/carve-write-' . bin2hex(random_bytes(5));
        mkdir($root);
        $path = $root . '/output.crv';
        file_put_contents($path, 'before');
        try {
            $this->expectException(RuntimeException::class);
            AtomicWrite::write($root . '/missing/output.crv', 'after');
        } finally {
            self::assertSame('before', file_get_contents($path));
            self::assertSame(['.', '..', 'output.crv'], scandir($root));
            unlink($path);
            rmdir($root);
        }
    }

    public function testReadOnlyTargetIsRefused(): void
    {
        $root = sys_get_temp_dir() . '/carve-ro-' . bin2hex(random_bytes(5));
        mkdir($root);
        $path = $root . '/output.crv';
        file_put_contents($path, 'before');
        chmod($path, 0444);
        try {
            AtomicWrite::write($path, 'after');
            self::fail('A read-only target must not be replaced.');
        } catch (RuntimeException) {
            self::assertSame('before', file_get_contents($path));
            self::assertSame(['.', '..', 'output.crv'], scandir($root));
        } finally {
            chmod($path, 0644);
            unlink($path);
            rmdir($root);
        }
    }
}
