<?php

declare(strict_types=1);

namespace MarkupCarve\CakeCarve\Test\V2;

use Cake\Core\Configure;
use Cake\View\View;
use MarkupCarve\CakeCarve\Carve\CarveRenderer;
use MarkupCarve\CakeCarve\Validation\CarveValidation;
use MarkupCarve\CakeCarve\View\Helper\CarveHelper;
use MarkupCarve\Carve2\Extension\Claim;
use MarkupCarve\Carve2\Extension\ExtensionDefinition;
use MarkupCarve\Carve2\Extension\ExtensionProvider;
use MarkupCarve\Carve2\Extension\RenderHost;
use MarkupCarve\Carve2\Node;
use MarkupCarve\Carve2\Output\OutputWriter;
use PHPUnit\Framework\TestCase;
use RuntimeException;

class Carve2PortTest extends TestCase
{
    public function testRenderAndSecureDefaults(): void
    {
        $renderer = new CarveRenderer();
        self::assertSame('<p><strong>bold</strong></p>', $renderer->toHtml('*bold*'));
        self::assertSame('<p>&lt;script&gt;x&lt;/script&gt;</p>', $renderer->toHtml('`<script>x</script>`{=html}'));
        self::assertSame('<p><a href="">go</a></p>', $renderer->toHtml('[go](javascript:alert(1))'));
    }

    public function testFrontmatterAndHeadingsAreCore(): void
    {
        $result = (new CarveRenderer(['extensions' => ['frontmatter', 'table_of_contents']]))
            ->render("---json\n{\"title\":\"Hi\"}\n---\n\n# One\n");
        self::assertSame('Hi', $result->meta('title'));
        self::assertSame([['level' => 1, 'id' => 'One', 'text' => 'One']], $result->toc());
        self::assertStringContainsString('<section id="One">', $result->html());
    }

    public function testExtensionProviderUsesCarve2Definition(): void
    {
        $provider = new class implements ExtensionProvider {
            public function definitions(): array
            {
                return [
                    new ExtensionDefinition(
                        'cake.example',
                        '1',
                        [],
                        matcher: ['form' => 'fence', 'name' => 'example'],
                        transform: static function (Claim $claim): Node {
                            $node = new Node('block_extension', []);
                            $node->f = [
                                'name' => 'cake.example',
                                'version' => '1',
                                'slots' => [],
                                'props' => [],
                                'fallback' => [$claim->copyNode()],
                            ];

                            return $node;
                        },
                        renderer: static fn (Node $node, RenderHost $host): string => '<aside>Example</aside>',
                    ),
                ];
            }
        };
        $renderer = new CarveRenderer(['extensions' => [$provider]]);
        self::assertSame('<aside>Example</aside>', $renderer->toHtml("```example\nbody\n```\n"));
    }

    public function testVersionedCacheSkipsStatefulExtension(): void
    {
        $extension = new ExtensionDefinition('cake.cache-test', '1');
        $renderer = new CarveRenderer([
            'extensions' => [$extension],
            'cache' => ['version' => 'v1'],
        ]);
        self::assertSame('<p>Hello</p>', $renderer->toHtml('Hello'));
    }

    public function testFileIncludesAndIdentity(): void
    {
        $root = sys_get_temp_dir() . '/cake-carve2-' . bin2hex(random_bytes(5));
        mkdir($root);
        file_put_contents($root . '/main.crv', "{{ part.crv }}\n");
        file_put_contents($root . '/part.crv', "Included *text*.\n");
        try {
            $renderer = new CarveRenderer(['includeRoot' => $root, 'cache' => ['config' => 'default']]);
            $result = $renderer->renderFile($root . '/main.crv');
            self::assertSame('<p>Included <strong>text</strong>.</p>', $result->html());
            self::assertSame([['path' => 'part.crv', 'resolved' => true, 'id' => 'part.crv']], $result->dependencies());
            file_put_contents($root . '/part.crv', "Changed.\n");
            self::assertSame('<p>Changed.</p>', $renderer->renderFile($root . '/main.crv')->html());
        } finally {
            unlink($root . '/main.crv');
            unlink($root . '/part.crv');
            rmdir($root);
        }
    }

    public function testFormatAndLint(): void
    {
        $renderer = new CarveRenderer();
        self::assertSame("a\nb\n", $renderer->format("a  \nb\n"));
        $findings = $renderer->lint("{#a}\nx\n\n{#a}\ny\n");
        self::assertSame('duplicate-id', $findings[0]['rule']);
        self::assertSame(4, $findings[0]['line']);
    }

    public function testLintFileUsesIncludes(): void
    {
        $root = sys_get_temp_dir() . '/cake-carve2-lint-' . bin2hex(random_bytes(5));
        mkdir($root);
        file_put_contents($root . '/main.crv', "{#a}\nx\n\n{#a}\ny\n");
        try {
            $findings = (new CarveRenderer(['includeRoot' => $root]))->lintFile($root . '/main.crv');
            self::assertSame('duplicate-id', $findings[0]['rule']);
            self::assertSame($root . '/main.crv', $findings[0]['file']);
        } finally {
            unlink($root . '/main.crv');
            rmdir($root);
        }
    }

    public function testIncludedFileLintPending(): void
    {
        $this->markTestSkipped('Carve2 included-file lint does not report duplicate-id yet; carve-php issue 5234.');
    }

    public function testHtmlImport(): void
    {
        $result = (new CarveRenderer())->import('<p>Hi <em>there</em></p>', 'html');
        self::assertSame("Hi /there/\n", $result->value);
        self::assertSame([], $result->losses());
        self::assertNotEmpty((new CarveRenderer())->import('<script>x</script>', 'html')->losses());
    }

    public function testLegacyImportsReturnFormattedSourceAndReviewDiagnostic(): void
    {
        $renderer = new CarveRenderer();
        foreach (
            [
                'markdown' => '**bold**',
                'djot' => '*bold*',
                'bbcode' => '[b]bold[/b]',
            ] as $from => $source
        ) {
            $result = $renderer->import($source, $from);
            self::assertSame("*bold*\n", $result->value);
            self::assertContains('legacy-import-review', array_map(static fn ($item): string => $item->code, $result->losses()));
        }
    }

    public function testWriterRegistryCanSupplyText(): void
    {
        $writer = new class implements OutputWriter {
            public function target(): string
            {
                return 'text';
            }

            public function write(Node $tree, array $options = []): string
            {
                return CarveRenderer::plainText($tree);
            }
        };
        $renderer = new CarveRenderer(['writers' => [$writer]]);
        self::assertSame('Hello', $renderer->toText('Hello'));
    }

    public function testUnavailableWriterGivesClearError(): void
    {
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('markdown writer not available yet');
        (new CarveRenderer())->toMarkdown('Hello');
    }

    public function testFileTargetUsesRegisteredWriter(): void
    {
        $writer = new class implements OutputWriter {
            public function target(): string
            {
                return 'text';
            }

            public function write(Node $tree, array $options = []): string
            {
                return CarveRenderer::plainText($tree);
            }
        };
        $path = tempnam(sys_get_temp_dir(), 'cake-carve2-target-');
        self::assertIsString($path);
        file_put_contents($path, 'Hello');
        try {
            self::assertSame('Hello', (new CarveRenderer(['writers' => [$writer]]))->renderFileAs($path, 'text'));
        } finally {
            unlink($path);
        }
    }

    public function testHelperMarkdownReportsWriterGap(): void
    {
        $helper = new CarveHelper(new View(), ['debug' => false]);
        self::assertSame('<p>Hello</p>', $helper->convert('Hello'));
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('markdown writer not available yet');
        $helper->markdown('Hello');
    }

    public function testValidationUsesCarve2TextNodes(): void
    {
        self::assertTrue(CarveValidation::valid('*Hi*', ['maxLength' => 2]));
        self::assertSame('Carve content is too long.', CarveValidation::valid('*Hello*', ['maxLength' => 2]));
    }

    public function testValidationCountsResolvedIncludeText(): void
    {
        $resolver = static fn (array $request): array => ['text' => "Included content.\n", 'id' => 'child.crv'];
        Configure::write('Carve.includeResolver', $resolver);
        try {
            self::assertSame('Carve content is too long.', CarveValidation::valid('{{ child.crv }}', ['maxLength' => 5]));
            self::assertTrue(CarveValidation::valid('{{ child.crv }}', ['minLength' => 5]));
        } finally {
            Configure::delete('Carve.includeResolver');
        }
    }

    public function testMarkdownWriterPending(): void
    {
        $this->markTestSkipped('Carve 0.2 markdown writer is not registered yet.');
    }

    public function testTextWriterPending(): void
    {
        $this->markTestSkipped('Carve 0.2 text writer is not registered yet.');
    }

    public function testAnsiWriterPending(): void
    {
        $this->markTestSkipped('Carve 0.2 ansi writer is not registered yet.');
    }

    public function testLegacyExtensionPending(): void
    {
        $this->markTestSkipped('Carve 0.2 heading_permalinks extension has no 0.2 definition yet.');
    }

    public function testInlineExtensionRendererPending(): void
    {
        $this->markTestSkipped('Carve2 inline extension renderer is pending in carve-php issue 5232.');
    }

    public function testStaticExtensionRendererPending(): void
    {
        $this->markTestSkipped('Carve2 static extension render channel is pending in carve issue 6070.');
    }

    public function testDocumentTransformPending(): void
    {
        $this->markTestSkipped('Carve2 document transform hook is pending in carve issue 6070.');
    }
}
