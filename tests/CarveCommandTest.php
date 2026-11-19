<?php

declare(strict_types=1);

namespace MarkupCarve\CakeCarve\Test;

use Cake\Console\TestSuite\ConsoleIntegrationTestTrait;
use Cake\TestSuite\TestCase;
use MarkupCarve\CakeCarve\Test\Fixtures\CarveTestApplication;

class CarveCommandTest extends TestCase
{
    use ConsoleIntegrationTestTrait;

    private string $root;

    protected function setUp(): void
    {
        parent::setUp();
        $this->configApplication(CarveTestApplication::class, [ROOT . '/config']);
        $this->root = sys_get_temp_dir() . '/cake-carve-commands-' . bin2hex(random_bytes(5));
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

    public function testRenderFormatLintAndImport(): void
    {
        $source = $this->root . '/input.crv';
        file_put_contents($source, "= Heading\n");
        $this->exec('carve render ' . $source);
        $this->assertExitCode(0);
        $this->assertOutputContains('<h1>Heading</h1>');

        $this->exec('carve format --check ' . $source);
        $this->assertExitCode(0);

        file_put_contents($source, "{#a}\nx\n\n{#a}\ny\n");
        $this->exec('carve lint ' . $source);
        $this->assertExitCode(1);
        $this->assertErrorContains('duplicate-id');

        $html = $this->root . '/input.html';
        file_put_contents($html, '<p>Hi <em>there</em></p>');
        $this->exec('carve convert --from html ' . $html);
        $this->assertExitCode(0);
        $this->assertOutputContains('Hi /there/');

        $markdown = $this->root . '/input.md';
        file_put_contents($markdown, '**bold**');
        $this->exec('carve convert --from markdown ' . $markdown);
        $this->assertExitCode(1);
        $this->assertErrorContains('Unknown Carve import format: markdown');
        $this->exec('carve convert --from markdown --check-loss ' . $markdown);
        $this->assertExitCode(1);
        $this->assertErrorContains('Unknown Carve import format: markdown');
    }

    public function testUnregisteredWriterAndImportLoss(): void
    {
        $source = $this->root . '/input.crv';
        file_put_contents($source, 'Hello');
        $this->exec('carve render --format markdown ' . $source);
        $this->assertExitCode(1);
        $this->assertErrorContains('markdown writer not available yet');

        $html = $this->root . '/loss.html';
        file_put_contents($html, '<script>x</script>');
        $this->exec('carve convert --from html --check-loss ' . $html);
        $this->assertExitCode(1);
        $this->assertErrorContains('dropped');
    }
}
