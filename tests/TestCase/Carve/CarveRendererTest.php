<?php

declare(strict_types=1);

namespace MarkupCarve\CakeCarve\Test\TestCase\Carve;

use Cake\Core\Configure;
use MarkupCarve\CakeCarve\Carve\CarveRenderer;
use MarkupCarve\CakeCarve\TestSuite\CarveAssertionsTrait;
use MarkupCarve\Carve\Renderer\SmartTypographyMode;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

class CarveRendererTest extends TestCase
{
    use CarveAssertionsTrait;

    public function testRenderAndText(): void
    {
        $renderer = new CarveRenderer();
        self::assertSame('<p>Hello</p>', trim($renderer->toHtml('Hello')));
        self::assertSame('Hello', trim($renderer->toText('Hello')));
        self::assertSame('Hello', trim($renderer->toMarkdown('Hello')));
        $this->assertCarveValid('Hello');
    }

    public function testImport(): void
    {
        $result = (new CarveRenderer())->import('**bold**', 'markdown');
        self::assertNotSame('', $result->value);
    }

    public function testResultWithFrontmatter(): void
    {
        $source = "---json\n{\"title\":\"Hi\"}\n---\n\n# One\n";
        $result = (new CarveRenderer(['extensions' => ['frontmatter']]))->render($source);
        self::assertTrue($result->hasFrontmatter());
        self::assertSame('Hi', $result->meta('title'));
        self::assertSame('One', $result->toc()[0]['text']);
        self::assertSame($result->html(), (string)$result);
        self::assertSame([], $result->warnings());
    }

    public function testPerCallOptionsDoNotLeak(): void
    {
        $renderer = new CarveRenderer();
        $source = "Hello\\\nworld";
        $unsafe = $renderer->toHtml($source, ['xhtml' => false]);
        $safe = $renderer->toHtml($source, ['xhtml' => true]);
        self::assertNotSame($unsafe, $safe);
    }

    public function testFileIncludes(): void
    {
        $root = sys_get_temp_dir() . '/cakecarve-' . bin2hex(random_bytes(5));
        mkdir($root);
        file_put_contents($root . '/main.crv', "# Main\n\n{{ one.crv }}\n");
        file_put_contents($root . '/one.crv', "Included.\n");
        try {
            $renderer = new CarveRenderer(['includeRoot' => $root, 'cache' => ['config' => 'default', 'prefix' => 'file_test_']]);
            $result = $renderer->renderFile($root . '/main.crv');
            self::assertStringContainsString('Included.', $result->html());
            self::assertSame([['path' => 'one.crv', 'resolved' => true]], $result->toArray()['dependencies']);
            file_put_contents($root . '/one.crv', "Changed.\n");
            self::assertStringContainsString('Changed.', $renderer->renderFile($root . '/main.crv')->html());
        } finally {
            unlink($root . '/main.crv');
            unlink($root . '/one.crv');
            rmdir($root);
        }
    }

    public function testCacheAndExtensionOptions(): void
    {
        $renderer = new CarveRenderer([
            'cache' => ['config' => 'default', 'prefix' => 'carve_test_'],
            'extensions' => [['type' => 'heading_permalinks', 'class' => 'permalink']],
        ]);
        $first = $renderer->render('# One');
        $second = $renderer->render('# One');
        self::assertSame($first->html(), $second->html());
        self::assertStringContainsString('permalink', $first->html());
    }

    /**
     * @return array<string, array{bool, string}>
     */
    public static function xhtmlCases(): array
    {
        return [
            'html' => [false, '<br>'],
            'xhtml' => [true, '<br />'],
        ];
    }

    #[DataProvider('xhtmlCases')]
    public function testXhtmlOption(bool $enabled, string $break): void
    {
        $html = (new CarveRenderer())->toHtml("Hello\\\nworld", ['xhtml' => $enabled]);
        self::assertStringContainsString($break, $html);
    }

    public function testInstanceExtensionListReplacesGlobalList(): void
    {
        Configure::write('Carve.extensions', ['unknown_extension', 'heading_permalinks']);
        try {
            $renderer = new CarveRenderer(['extensions' => ['table_of_contents']]);
            $result = $renderer->render('Hello');
            self::assertStringContainsString('Hello', $result->html());
        } finally {
            Configure::delete('Carve.extensions');
        }
    }

    public function testSmartTypographyEnum(): void
    {
        $renderer = new CarveRenderer();
        self::assertNotSame('', $renderer->toHtml('Hello', ['smartTypography' => SmartTypographyMode::Glyph]));
    }
}
