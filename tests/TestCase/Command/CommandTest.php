<?php

declare(strict_types=1);

namespace MarkupCarve\CakeCarve\Test\TestCase\Command;

use Cake\Console\Arguments;
use Cake\Console\CommandCollection;
use Cake\Console\ConsoleIo;
use Cake\Console\TestSuite\StubConsoleOutput;
use MarkupCarve\CakeCarve\CakeCarvePlugin;
use MarkupCarve\CakeCarve\Command\ConvertCommand;
use MarkupCarve\CakeCarve\Command\FormatCommand;
use MarkupCarve\CakeCarve\Command\LintCommand;
use MarkupCarve\CakeCarve\Command\RenderCommand;
use PHPUnit\Framework\TestCase;

class CommandTest extends TestCase
{
    public function testPluginRegistersFourCommands(): void
    {
        $commands = (new CakeCarvePlugin())->console(new CommandCollection());
        foreach (['carve render', 'carve convert', 'carve format', 'carve lint'] as $name) {
            self::assertTrue($commands->has($name));
        }
    }

    public function testCommandsReadAndWriteFiles(): void
    {
        $root = sys_get_temp_dir() . '/cakecarve-command-' . bin2hex(random_bytes(5));
        mkdir($root);
        file_put_contents($root . '/in.crv', "Hello\n");
        file_put_contents($root . '/in.md', '**Hello**');
        $out = new StubConsoleOutput();
        $err = new StubConsoleOutput();
        $io = new ConsoleIo($out, $err);
        try {
            self::assertSame(0, (new RenderCommand())->execute(
                new Arguments([$root . '/in.crv'], ['output' => $root . '/out.html'], ['file']),
                $io,
            ));
            $html = file_get_contents($root . '/out.html');
            self::assertIsString($html);
            self::assertStringContainsString('<p>Hello</p>', $html);
            self::assertSame(0, (new ConvertCommand())->execute(
                new Arguments([$root . '/in.md'], ['from' => 'markdown', 'output' => $root . '/out.crv'], ['file']),
                $io,
            ));
            self::assertNotSame('', file_get_contents($root . '/out.crv'));
            self::assertSame(0, (new FormatCommand())->execute(
                new Arguments([[$root . '/in.crv']], ['write' => true], ['files']),
                $io,
            ));
            self::assertSame(0, (new LintCommand())->execute(
                new Arguments([[$root . '/in.crv']], [], ['files']),
                $io,
            ));
            file_put_contents($root . '/unformatted.crv', 'Hello');
            self::assertSame(1, (new FormatCommand())->execute(
                new Arguments([[$root . '/unformatted.crv']], ['check' => true], ['files']),
                $io,
            ));
            file_put_contents($root . '/lint.crv', '**bold**');
            self::assertSame(1, (new LintCommand())->execute(
                new Arguments([[$root . '/lint.crv']], [], ['files']),
                $io,
            ));
            file_put_contents($root . '/loss.html', '<script>x</script>');
            self::assertSame(1, (new ConvertCommand())->execute(
                new Arguments([$root . '/loss.html'], ['from' => 'html', 'check-loss' => true], ['file']),
                $io,
            ));
            file_put_contents($root . '/include.crv', "{{ missing.crv }}\n");
            self::assertSame(1, (new LintCommand())->execute(
                new Arguments([[$root . '/include.crv']], ['include-root' => $root], ['files']),
                $io,
            ));
        } finally {
            foreach (glob($root . '/*') ?: [] as $file) {
                unlink($file);
            }
            rmdir($root);
        }
    }
}
