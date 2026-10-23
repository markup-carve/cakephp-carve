<?php

declare(strict_types=1);

namespace MarkupCarve\CakeCarve\Test\TestCase\Carve;

use Cake\Cache\Cache;
use Cake\Core\Configure;
use InvalidArgumentException;
use MarkupCarve\CakeCarve\Carve\CarveRenderer;
use MarkupCarve\Carve\CarveConverter;
use MarkupCarve\Carve\Exception\ParseException;
use MarkupCarve\Carve\Exception\ProfileViolationException;
use MarkupCarve\Carve\Extension\SpoilerExtension;
use MarkupCarve\Carve\Profile;
use MarkupCarve\Carve\Renderer\MarkdownRenderer;
use MarkupCarve\Carve\Renderer\PlainTextRenderer;
use MarkupCarve\Carve\Renderer\SoftBreakMode;
use MarkupCarve\Carve\SafeMode;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use ReflectionMethod;
use ReflectionProperty;

class CarveRendererOptionsTest extends TestCase
{
    public function testHeadingEmphasisTextMarkdownAndCacheIsolation(): void
    {
        $renderer = new CarveRenderer();
        $source = "# Header\n\nMy *bold* and /italic/ text with `code`.\n\n## Header 2\n\nFoo bar.";
        self::assertSame((new CarveConverter(safeMode: true))->convert($source), $renderer->toHtml($source));
        self::assertStringContainsString('<em>italic</em>', $renderer->toHtml($source));
        self::assertStringContainsString('<strong>bold</strong>', $renderer->toHtml($source));
        self::assertStringContainsString('<code>code</code>', $renderer->toHtml($source));
        $link = '[with links](http://example.com)';
        self::assertSame((new CarveConverter(safeMode: true))->convert($link), $renderer->toHtml($link));
        self::assertStringContainsString('<a href="http://example.com">with links</a>', $renderer->toHtml($link));
        self::assertSame((new CarveConverter(renderer: new PlainTextRenderer(), safeMode: true))->convert($source), $renderer->toText($source));
        self::assertSame((new CarveConverter(renderer: new MarkdownRenderer(), safeMode: true))->convert($source), $renderer->toMarkdown($source));
        $raw = 'Some `<b>raw html</b>`{=html} inline.';
        self::assertStringContainsString('<b>raw html</b>', $renderer->toHtml($raw, ['safeMode' => false]));
        self::assertStringNotContainsString('<b>raw html</b>', $renderer->toHtml($raw));
        $renderer->toText('# Heading');
        $renderer->toMarkdown('# Heading');
        $converters = (new ReflectionProperty($renderer, 'converters'))->getValue($renderer);
        self::assertIsArray($converters);
        self::assertCount(3, $converters);
    }

    /**
     * @return array<string, array{string, array<string, mixed>}>
     */
    public static function unsafeSources(): array
    {
        $sources = [
            'javascript link' => '[x](javascript:alert(1))',
            'data link' => '[x](data:text/html,<script>alert(1)</script>)',
            'onclick' => 'Text{onclick="alert(1)"}',
            'raw block' => "```=html\n<script>alert(1)</script>\n```",
            'raw span' => '`<script>alert(1)</script>`{=html}',
            'prose script' => '<script>alert(1)</script>',
        ];
        $cases = [];
        foreach ($sources as $name => $source) {
            $cases[$name . ' default'] = [$source, []];
            $cases[$name . ' strict'] = [$source, ['safeMode' => 'strict']];
        }

        return $cases;
    }

    /**
     * @param string $source
     * @param array<string, mixed> $options
     */
    #[DataProvider('unsafeSources')]
    public function testSafeOutput(string $source, array $options): void
    {
        $safe = isset($options['safeMode']) && $options['safeMode'] === 'strict' ? SafeMode::strict() : true;
        $direct = new CarveConverter(safeMode: $safe);
        $html = (new CarveRenderer())->toHtml($source, $options);
        self::assertSame($direct->convert($source), $html);
        self::assertStringNotContainsString('javascript:', $html);
        self::assertDoesNotMatchRegularExpression('/<[^>]+\sonclick\s*=/i', $html);
        self::assertStringNotContainsString('<script>', $html);
    }

    /**
     * @return array<string, array{array<string, mixed>, \MarkupCarve\Carve\CarveConverter, string}>
     */
    public static function engineOptions(): array
    {
        return [
            'static mode' => [['mode' => 'static', 'extensions' => ['spoiler']], new CarveConverter(safeMode: true, mode: 'static'), ':spoiler[secret]'],
            'soft break' => [['softBreakMode' => 'br'], new CarveConverter(safeMode: true, softBreakMode: SoftBreakMode::Break), "one\ntwo"],
            'typography' => [['smartTypography' => false], new CarveConverter(safeMode: true, smartTypography: false), '"Hello"'],
            'symbols' => [['symbols' => ['copy' => 'X']], new CarveConverter(safeMode: true, symbols: ['copy' => 'X']), ':copy:'],
            'source lines' => [['sourceLines' => true], new CarveConverter(safeMode: true, sourceLines: true), '# Heading'],
            'xhtml' => [['xhtml' => true], new CarveConverter(safeMode: true, xhtml: true), "one\\\ntwo"],
            'labels' => [['labels' => ['footnoteBacklink' => 'Zurück zum Text']], new CarveConverter(safeMode: true, labels: ['footnoteBacklink' => 'Zurück zum Text']), "text[^1]\n\n[^1]: note"],
            'round trip' => [['roundTripMode' => true], new CarveConverter(safeMode: true, roundTripMode: true), "text[^1]\n\n[^1]: note"],
        ];
    }

    /**
     * @param array<string, mixed> $options
     * @param string $source
     * @param \MarkupCarve\Carve\CarveConverter $direct
     */
    #[DataProvider('engineOptions')]
    public function testEngineOptions(array $options, CarveConverter $direct, string $source): void
    {
        if ($options['mode'] ?? null) {
            $direct->addExtension(new SpoilerExtension());
        }
        $actual = (new CarveRenderer())->toHtml($source, $options);
        self::assertSame($direct->convert($source), $actual);
        $extensions = $options['extensions'] ?? [];
        if (is_array($extensions) && in_array('spoiler', $extensions, true)) {
            self::assertNotSame((new CarveRenderer())->toHtml($source, ['extensions' => ['spoiler']]), $actual);
        } elseif (array_intersect_key($options, array_flip(['smartTypography', 'symbols', 'labels', 'roundTripMode'])) !== []) {
            self::assertNotSame((new CarveRenderer())->toHtml($source), $actual);
        }
    }

    public function testBuildTimeRenderersRenderInStaticModeAndSkipTheCache(): void
    {
        Cache::setConfig('carve_renderers', ['className' => 'Array']);
        $calls = 0;
        $options = [
            'mode' => 'static',
            'extensions' => ['mermaid'],
            'renderers' => [

                'mermaid' => function (string $source) use (&$calls): string {
                    $calls++;

                    return '<svg data-src="' . htmlspecialchars($source) . '"></svg>';
                },
            ],
            'cache' => ['config' => 'carve_renderers'],
        ];
        try {
            $renderer = new CarveRenderer();
            $html = $renderer->toHtml("```mermaid\ngraph A\n```", $options);
            self::assertStringContainsString('<svg data-src="graph A"></svg>', $html);
            $renderer->toHtml("```mermaid\ngraph A\n```", $options);
            self::assertSame(2, $calls, 'A closure renderer must not be served from the cache.');
        } finally {
            Cache::drop('carve_renderers');
        }
    }

    public function testEveryEngineOptionIsAPluginOption(): void
    {
        $options = (new CarveRenderer())->resolveOptions();
        foreach ((new ReflectionMethod(CarveConverter::class, '__construct'))->getParameters() as $parameter) {
            if (in_array($parameter->getName(), ['parser', 'renderer'], true)) {
                continue;
            }
            self::assertArrayHasKey($parameter->getName(), $options, 'carve-php option `' . $parameter->getName() . '` is not configurable through the plugin.');
        }
    }

    public function testRenderersOnlyAcceptClosures(): void
    {
        $this->expectException(InvalidArgumentException::class);
        (new CarveRenderer())->toHtml('x', ['renderers' => ['mermaid' => 'strtoupper']]);
    }

    public function testStrictParseErrorMatchesEngine(): void
    {
        $source = "```carve\nunclosed";
        try {
            (new CarveConverter(strict: true, safeMode: true))->convert($source);
            self::fail('Engine did not reject the parse error');
        } catch (ParseException $engineError) {
            $this->expectException(ParseException::class);
            $this->expectExceptionMessage($engineError->getMessage());
            (new CarveRenderer())->toHtml($source, ['strict' => true]);
        }
    }

    public function testUnknownPresetAndProfile(): void
    {
        $renderer = new CarveRenderer();
        foreach ([['preset' => 'missing'], ['profile' => 'missing']] as $options) {
            try {
                $renderer->toHtml('Text', $options);
                self::fail('Unknown option accepted');
            } catch (InvalidArgumentException $exception) {
                self::assertInstanceOf(InvalidArgumentException::class, $exception);
            }
        }
    }

    public function testConfiguredPresetAndPerCallOverride(): void
    {
        Configure::write('Carve.presets', ['trusted' => ['safeMode' => true]]);
        try {
            $renderer = new CarveRenderer();
            $source = '`<b>raw</b>`{=html}';
            self::assertSame((new CarveConverter(safeMode: true))->convert($source), $renderer->toHtml($source, ['preset' => 'trusted']));
            self::assertSame((new CarveConverter(safeMode: false))->convert($source), $renderer->toHtml($source, ['preset' => 'trusted', 'safeMode' => false]));
        } finally {
            Configure::delete('Carve.presets');
        }
    }

    public function testCommentProfileDisallowedActions(): void
    {
        $source = '# Heading';
        foreach (['strip', 'to_text'] as $action) {
            $direct = new CarveConverter(safeMode: SafeMode::strict(), profile: Profile::comment()->onDisallowed($action));
            $actual = (new CarveRenderer())->toHtml($source, ['preset' => 'comment', 'onDisallowed' => $action]);
            self::assertSame($direct->convert($source), $actual);
            if ($action === 'strip') {
                self::assertStringNotContainsString('Heading', $actual);
            } else {
                self::assertStringContainsString('# Heading', $actual);
            }
        }
        $this->expectException(ProfileViolationException::class);
        (new CarveRenderer())->toHtml($source, ['preset' => 'comment', 'onDisallowed' => 'error']);
    }

    public function testTrustedRawHtmlAndUnknownExtension(): void
    {
        $source = '`<b>raw</b>`{=html}';
        self::assertSame((new CarveConverter(safeMode: false))->convert($source), (new CarveRenderer())->toHtml($source, ['preset' => 'trusted']));
        self::assertStringContainsString('<b>raw</b>', (new CarveRenderer())->toHtml($source, ['safeMode' => false]));
        $block = "```=html\n<b>raw block</b>\n```";
        self::assertSame((new CarveConverter(safeMode: false))->convert($block), (new CarveRenderer())->toHtml($block, ['preset' => 'trusted']));
        self::assertStringContainsString('<b>raw block</b>', (new CarveRenderer())->toHtml($block, ['preset' => 'trusted']));
        $this->expectException(InvalidArgumentException::class);
        (new CarveRenderer())->toHtml('Text', ['extensions' => ['unknown_extension']]);
    }
}
