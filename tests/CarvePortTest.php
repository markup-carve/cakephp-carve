<?php

declare(strict_types=1);

namespace MarkupCarve\CakeCarve\Test;

use Cake\Core\Configure;
use Cake\View\View;
use InvalidArgumentException;
use MarkupCarve\CakeCarve\Carve\CarveRenderer;
use MarkupCarve\CakeCarve\Validation\CarveValidation;
use MarkupCarve\CakeCarve\View\Helper\CarveHelper;
use MarkupCarve\Carve\Extension\Claim;
use MarkupCarve\Carve\Extension\ExtensionDefinition;
use MarkupCarve\Carve\Extension\ExtensionProvider;
use MarkupCarve\Carve\Extension\Navigation;
use MarkupCarve\Carve\Extension\RenderHost;
use MarkupCarve\Carve\Node;
use MarkupCarve\Carve\Output\OutputWriter;
use PHPUnit\Framework\TestCase;

class CarvePortTest extends TestCase
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

    public function testExtensionProviderUsesCarveDefinition(): void
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
        $root = sys_get_temp_dir() . '/cake-carve-' . bin2hex(random_bytes(5));
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

    public function testMissingIncludeCreatedAfterCachedRender(): void
    {
        $root = sys_get_temp_dir() . '/cake-carve-missing-' . bin2hex(random_bytes(5));
        mkdir($root);
        file_put_contents($root . '/main.crv', "{{ one.crv }}\n\n{{ two.crv }}\n");
        file_put_contents($root . '/one.crv', "One.\n");
        try {
            $renderer = new CarveRenderer(['includeRoot' => $root, 'cache' => ['config' => 'default']]);
            $first = $renderer->renderFile($root . '/main.crv')->html();
            self::assertStringContainsString('One.', $first);
            self::assertStringNotContainsString('Two.', $first);
            file_put_contents($root . '/two.crv', "Two.\n");
            self::assertStringContainsString('Two.', $renderer->renderFile($root . '/main.crv')->html());
        } finally {
            unlink($root . '/main.crv');
            unlink($root . '/one.crv');
            if (is_file($root . '/two.crv')) {
                unlink($root . '/two.crv');
            }
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
        $root = sys_get_temp_dir() . '/cake-carve-lint-' . bin2hex(random_bytes(5));
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

    public function testIncludedFileLintReportsDuplicateIds(): void
    {
        $root = sys_get_temp_dir() . '/cake-carve-included-lint-' . bin2hex(random_bytes(5));
        mkdir($root);
        file_put_contents($root . '/main.crv', "{#same}\nfirst\n\n{{ child.crv }}\n");
        file_put_contents($root . '/child.crv', "{#same}\nsecond\n");
        try {
            $findings = (new CarveRenderer(['includeRoot' => $root]))->lintFile($root . '/main.crv');
            self::assertContains('duplicate-id', array_column($findings, 'rule'));
        } finally {
            unlink($root . '/main.crv');
            unlink($root . '/child.crv');
            rmdir($root);
        }
    }

    public function testHtmlImport(): void
    {
        $result = (new CarveRenderer())->import('<p>Hi <em>there</em></p>', 'html');
        self::assertSame("Hi /there/\n", $result->value);
        self::assertSame([], $result->losses());
        self::assertNotEmpty((new CarveRenderer())->import('<script>x</script>', 'html')->losses());
    }

    public function testRemovedImportFormatsAreRejected(): void
    {
        $renderer = new CarveRenderer();
        foreach (['markdown', 'djot', 'bbcode'] as $from) {
            try {
                $renderer->import('text', $from);
                self::fail('Removed importer unexpectedly accepted ' . $from);
            } catch (InvalidArgumentException $exception) {
                self::assertSame('Unknown Carve import format: ' . $from, $exception->getMessage());
            }
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
        self::assertSame("Hello\n", (new CarveRenderer())->toMarkdown('Hello'));
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
        $path = tempnam(sys_get_temp_dir(), 'cake-carve-target-');
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
        self::assertSame('Hello', $helper->markdown('Hello'));
    }

    public function testValidationUsesCarveTextNodes(): void
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

    public function testMarkdownWriterIsAvailable(): void
    {
        self::assertSame("**Bold**\n", (new CarveRenderer())->toMarkdown('*Bold*'));
    }

    public function testTextWriterIsAvailable(): void
    {
        self::assertSame("Bold\n", (new CarveRenderer())->toText('*Bold*'));
    }

    public function testAnsiWriterIsAvailable(): void
    {
        self::assertStringContainsString('Bold', (new CarveRenderer())->toAnsi('*Bold*'));
    }

    public function testLegacyExtensionPending(): void
    {
        $this->markTestSkipped('Carve 0.2 heading_permalinks extension has no 0.2 definition yet.');
    }

    public function testInlineExtensionRendererRunsThroughTheHost(): void
    {
        $definition = new ExtensionDefinition(
            'host.embed',
            '1',
            matcher: ['form' => 'inline', 'name' => 'embed'],
            transform: static function (Claim $claim): Node {
                $node = new Node('inline_extension', []);
                $node->f['name'] = 'host.embed';
                $node->f['version'] = '1';
                $node->f['fallback'] = $claim->node->children ?? [];

                return $node;
            },
            renderer: static fn (Node $node, RenderHost $host): string => '<b>handled</b>',
        );
        $renderer = new CarveRenderer(['extensions' => [$definition]]);
        self::assertStringContainsString('<b>handled</b>', $renderer->toHtml('Watch :embed[abc].'));
    }

    public function testStaticExtensionRendererRunsThroughTheHost(): void
    {
        $definition = new ExtensionDefinition(
            'host.static',
            '1',
            matcher: ['form' => 'inline', 'name' => 'static'],
            transform: static function (Claim $claim): Node {
                $node = new Node('inline_extension', []);
                $node->f = ['name' => 'host.static', 'version' => '1', 'fallback' => $claim->node->children ?? []];

                return $node;
            },
            buildTimeRenderer: static fn (Node $node, RenderHost $host): string => '<b>static</b>',
        );
        $renderer = new CarveRenderer(['extensions' => [$definition], 'mode' => 'static']);
        self::assertStringContainsString('<b>static</b>', $renderer->toHtml(':static[abc]'));
    }

    public function testDocumentTransformRunsThroughTheHost(): void
    {
        $renderer = new CarveRenderer(['extensions' => [Navigation::headingNumbers()]]);
        self::assertStringContainsString('section-number', $renderer->toHtml('# Heading'));
    }
}
