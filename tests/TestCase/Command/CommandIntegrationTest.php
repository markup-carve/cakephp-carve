<?php

declare(strict_types=1);

namespace MarkupCarve\CakeCarve\Test\TestCase\Command;

use Cake\Console\TestSuite\ConsoleIntegrationTestTrait;
use Cake\TestSuite\TestCase;
use MarkupCarve\CakeCarve\Carve\CarveRenderer;

class CommandIntegrationTest extends TestCase
{
    use ConsoleIntegrationTestTrait;

    private string $root;

    protected function setUp(): void
    {
        parent::setUp();
        $this->configApplication(CarveTestApplication::class, [ROOT . '/config']);
        $this->root = sys_get_temp_dir() . '/cakecarve-integration-' . bin2hex(random_bytes(5));
        mkdir($this->root);
    }

    protected function tearDown(): void
    {
        foreach (glob($this->root . '/*') ?: [] as $file) {
            unlink($file);
        }
        rmdir($this->root);
        parent::tearDown();
    }

    public function testFormatCheckAndWrite(): void
    {
        $path = $this->root . '/input.crv';
        file_put_contents($path, 'Hello');
        $this->exec('carve format --check ' . $path);
        $this->assertExitCode(1);
        $this->exec('carve format --write ' . $path);
        $this->assertExitCode(0);
        self::assertSame((new CarveRenderer())->format('Hello'), file_get_contents($path));
        $this->exec('carve format --check ' . $path);
        $this->assertExitCode(0);
        $this->exec('carve format ' . $path);
        $this->assertExitCode(0);
        self::assertSame([(new CarveRenderer())->format('Hello')], $this->_out?->messages());
        $second = $this->root . '/second.crv';
        file_put_contents($second, 'Second');
        $this->exec('carve format --write ' . $path . ',' . $second);
        $this->assertExitCode(0);
        self::assertSame((new CarveRenderer())->format('Second'), file_get_contents($second));
    }

    public function testLintAndMissingFile(): void
    {
        $path = $this->root . '/input.crv';
        file_put_contents($path, '**bold**');
        $this->exec('carve lint ' . $path);
        $this->assertExitCode(1);
        file_put_contents($path, '*bold*');
        $this->exec('carve lint ' . $path);
        $this->assertExitCode(0);
        $this->exec('carve render ' . $this->root . '/missing.crv');
        $this->assertExitCode(1);
        $this->assertErrorContains('Unreadable Carve file');
    }

    public function testConvertAndRenderOutputs(): void
    {
        $source = $this->root . '/input.md';
        $render = $this->root . '/render.crv';
        $html = $this->root . '/output.html';
        file_put_contents($source, '**bold**');
        file_put_contents($render, '# Heading');
        $this->exec('carve convert --from markdown ' . $source);
        $this->assertExitCode(0);
        $this->assertOutputContains('*bold*');
        $this->exec('carve render --output ' . $html . ' ' . $render);
        $this->assertExitCode(0);
        self::assertSame((new CarveRenderer())->toHtml('# Heading'), file_get_contents($html));
        $this->exec('carve render ' . $render);
        $this->assertExitCode(0);
        $this->assertOutputContains('<h1>Heading</h1>');
        $loss = $this->root . '/loss.html';
        file_put_contents($loss, '<script>x</script>');
        $this->exec('carve convert --from html --check-loss ' . $loss);
        $this->assertExitCode(1);

        $kept = $this->root . '/kept.crv';
        file_put_contents($kept, 'existing');
        $this->exec('carve convert --from html --check-loss --output ' . $kept . ' ' . $loss);
        $this->assertExitCode(1);
        self::assertSame('existing', file_get_contents($kept));
    }
}
