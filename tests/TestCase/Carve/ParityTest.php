<?php

declare(strict_types=1);

namespace MarkupCarve\CakeCarve\Test\TestCase\Carve;

use Cake\Cache\Cache;
use Closure;
use MarkupCarve\CakeCarve\Carve\CarveRenderer;
use MarkupCarve\CakeCarve\Carve\IncludeCacheKeyProvider;
use MarkupCarve\Carve\CarveConverter;
use MarkupCarve\Carve\Extension\FencedRenderExtension;
use MarkupCarve\Carve\Extension\TableOfContentsExtension;
use MarkupCarve\Carve\Extension\WikilinksExtension;
use MarkupCarve\Carve\Renderer\AnsiRenderer;
use MarkupCarve\Carve\Renderer\MarkdownRenderer;
use MarkupCarve\Carve\Renderer\PlainTextRenderer;
use MarkupCarve\Carve\SafeMode;
use MarkupCarve\Carve\Transform\IncludeContext;
use MarkupCarve\Carve\Transform\IncludeResolverInterface;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use ReflectionMethod;

class ParityTest extends TestCase
{
    public function testAnsiAndAlignedFormattingUseEngine(): void
    {
        $source = "# Heading\n";
        self::assertSame((new CarveConverter(renderer: new AnsiRenderer(), safeMode: true))->convert($source), (new CarveRenderer())->toAnsi($source));
        $table = "| a | long |\n|---|---|\n| x | yy |\n";
        self::assertSame(CarveConverter::toCarve($table, alignTables: true), (new CarveRenderer())->format($table, true));
    }

    public function testRawHtmlModesUseEngine(): void
    {
        $source = '`<b>x</b>`{=html}';
        foreach ([SafeMode::RAW_HTML_STRIP, SafeMode::RAW_HTML_ESCAPE, SafeMode::RAW_HTML_ALLOW] as $mode) {
            $safe = (new SafeMode())->setRawHtmlMode($mode);
            self::assertSame((new CarveConverter(safeMode: $safe))->convert($source), (new CarveRenderer())->toHtml($source, ['rawHtml' => $mode]));
        }
    }

    /**
     * @return array<string, array{bool|string, string}>
     */
    public static function rawModes(): array
    {
        $cases = [];
        foreach ([true, 'strict'] as $safe) {
            foreach ([SafeMode::RAW_HTML_STRIP, SafeMode::RAW_HTML_ESCAPE, SafeMode::RAW_HTML_ALLOW] as $raw) {
                $cases[(is_bool($safe) ? 'true' : $safe) . '-' . $raw] = [$safe, $raw];
            }
        }

        return $cases;
    }

    #[DataProvider('rawModes')]
    public function testStrictRawHtmlCannotBeLoosened(string|bool $safe, string $raw): void
    {
        $source = "`<script>alert(1)</script>`{=html}\n";
        $actual = (new CarveRenderer())->toHtml($source, ['safeMode' => $safe, 'rawHtml' => $raw]);
        $engineSafe = $safe === 'strict' ? SafeMode::strict() : (new SafeMode())->setRawHtmlMode($raw);
        self::assertSame((new CarveConverter(safeMode: $engineSafe))->convert($source), $actual);
        self::assertSame($safe === true && $raw === SafeMode::RAW_HTML_ALLOW, str_contains($actual, '<script>'));
    }

    public function testExplicitSafeModeKeepsItsOwnRawHtmlHandling(): void
    {
        $source = "```=html\n<script>alert(1)</script>\n```\n";
        $strict = (new CarveRenderer())->toHtml($source, ['safeMode' => SafeMode::strict(), 'rawHtml' => SafeMode::RAW_HTML_ALLOW]);
        self::assertStringNotContainsString('<script>', $strict);

        $escaping = (new SafeMode())->setRawHtmlMode(SafeMode::RAW_HTML_ESCAPE);
        $actual = (new CarveRenderer())->toHtml($source, ['safeMode' => $escaping, 'rawHtml' => SafeMode::RAW_HTML_ALLOW]);
        self::assertSame((new CarveConverter(safeMode: $escaping))->convert($source), $actual);
    }

    public function testCacheVersionKeepsDifferentClosuresApart(): void
    {
        Cache::setConfig('carve_closures', ['className' => 'Array']);
        $source = "```mermaid\ngraph A\n```";
        $options = static fn (Closure $render): array => [
            'mode' => 'static',
            'extensions' => ['mermaid'],
            'renderers' => ['mermaid' => $render],
            'cache' => ['config' => 'carve_closures', 'version' => 'v1'],
        ];
        try {
            $first = (new CarveRenderer())->toHtml($source, $options(static fn (string $s): string => '<b>first</b>'));
            $second = (new CarveRenderer())->toHtml($source, $options(static fn (string $s): string => '<b>second</b>'));
            self::assertStringContainsString('first', $first);
            self::assertStringContainsString('second', $second);
        } finally {
            Cache::drop('carve_closures');
        }
    }

    public function testCacheVersionKeepsSameSiteClosuresWithDifferentCapturesApart(): void
    {
        Cache::setConfig('carve_captures', ['className' => 'Array']);
        $source = "```mermaid\ngraph A\n```";
        $renderer = static fn (string $label): Closure => static fn (string $s): string => '<b>' . $label . '</b>';
        $options = static fn (Closure $render): array => [
            'mode' => 'static',
            'extensions' => ['mermaid'],
            'renderers' => ['mermaid' => $render],
            'cache' => ['config' => 'carve_captures', 'version' => 'v1'],
        ];
        try {
            self::assertStringContainsString('<b>one</b>', (new CarveRenderer())->toHtml($source, $options($renderer('one'))));
            self::assertStringContainsString('<b>two</b>', (new CarveRenderer())->toHtml($source, $options($renderer('two'))));
        } finally {
            Cache::drop('carve_captures');
        }
    }

    public function testCacheVersionSkipsClosuresWhoseCapturesCannotBeKeyed(): void
    {
        Cache::setConfig('carve_unkeyable', ['className' => 'Array']);
        $calls = 0;
        $inner = static fn (): string => 'x';
        $render = function (string $s) use (&$calls, $inner): string {
            $calls++;

            return '<b>' . $inner() . '</b>';
        };
        $options = [
            'mode' => 'static',
            'extensions' => ['mermaid'],
            'renderers' => ['mermaid' => $render],
            'cache' => ['config' => 'carve_unkeyable', 'version' => 'v1'],
        ];
        try {
            (new CarveRenderer())->toHtml("```mermaid\ngraph A\n```", $options);
            (new CarveRenderer())->toHtml("```mermaid\ngraph A\n```", $options);
            self::assertSame(2, $calls, 'A closure capturing a closure cannot be keyed and must not be cached.');
        } finally {
            Cache::drop('carve_unkeyable');
        }
    }

    public function testConfiguredExtensionRunsOnEveryNonHtmlTarget(): void
    {
        $source = '[[Hello]]';
        foreach (['text' => new PlainTextRenderer(), 'markdown' => new MarkdownRenderer(), 'ansi' => new AnsiRenderer()] as $target => $engineRenderer) {
            $engine = new CarveConverter(renderer: $engineRenderer, safeMode: true);
            $engine->addExtension(new WikilinksExtension());
            $actual = match ($target) {
                'text' => (new CarveRenderer(['extensions' => ['wikilinks']]))->toText($source),
                'markdown' => (new CarveRenderer(['extensions' => ['wikilinks']]))->toMarkdown($source),
                'ansi' => (new CarveRenderer(['extensions' => ['wikilinks']]))->toAnsi($source),
            };
            self::assertSame($engine->convert($source), $actual);
        }
    }

    /**
     * @return array<string, array{string, string}>
     */
    public static function diagramPresets(): array
    {
        return [
            'd2' => ['d2', 'd2'],
            'graphviz' => ['graphviz', 'dot'],
            'wavedrom' => ['wavedrom', 'wavedrom'],
            'abc' => ['abc', 'abc'],
            'vega_lite' => ['vega_lite', 'vega-lite'],
            'chart' => ['chart', 'chart'],
        ];
    }

    #[DataProvider('diagramPresets')]
    public function testDiagramPresetMatchesEngine(string $name, string $language): void
    {
        $source = "```$language\nexample\n```\n";
        $engine = new CarveConverter(safeMode: true);
        $method = $name === 'vega_lite' ? 'vegaLite' : $name;
        $engine->addExtension(FencedRenderExtension::{$method}());
        self::assertSame($engine->convert($source), (new CarveRenderer(['extensions' => [$name]]))->toHtml($source));
    }

    public function testCacheVersionEnablesMutableExtensions(): void
    {
        $renderer = new CarveRenderer(['cache' => ['config' => 'default', 'version' => 'v1']]);
        $key = (new ReflectionMethod($renderer, 'cacheKey'))->invoke($renderer, 'string', 'Hello', $renderer->resolveOptions(['extensions' => [new TableOfContentsExtension()]]));
        self::assertIsString($key);
        self::assertSame($key, (new ReflectionMethod($renderer, 'cacheKey'))->invoke($renderer, 'string', 'Hello', $renderer->resolveOptions(['extensions' => [new TableOfContentsExtension()]])));
        self::assertNotSame($key, (new ReflectionMethod($renderer, 'cacheKey'))->invoke($renderer, 'string', 'Hello', $renderer->resolveOptions(['cache' => ['config' => 'default', 'version' => 'v2'], 'extensions' => [new TableOfContentsExtension()]])));
    }

    public function testCustomResolverAndCacheKey(): void
    {
        $path = tempnam(sys_get_temp_dir(), 'carve-parity-');
        self::assertIsString($path);
        file_put_contents($path, "{{ child.crv }}\n");
        try {
            $resolver = new class implements IncludeResolverInterface, IncludeCacheKeyProvider {
                public string $content = 'Child';

                public string $version = 'v1';

                public function resolve(string $path, IncludeContext $context): ?string
                {
                    return $path === 'child.crv' ? $this->content : null;
                }

                public function includeCacheKey(array $dependencies): string
                {
                    return $this->version;
                }
            };
            $renderer = new CarveRenderer(['includeResolver' => $resolver, 'cache' => ['config' => 'default', 'prefix' => 'parity_' . bin2hex(random_bytes(4)) . '_']]);
            self::assertStringContainsString('Child', $renderer->renderFile($path)->html());
            self::assertStringContainsString('Child', $renderer->renderFileAs($path, 'text'));
            $resolver->content = 'Changed';
            self::assertStringContainsString('Child', $renderer->renderFile($path)->html());
            $resolver->version = 'v2';
            self::assertStringContainsString('Changed', $renderer->renderFile($path)->html());
            $uncached = new class implements IncludeResolverInterface {
                public string $content = 'First';

                public function resolve(string $path, IncludeContext $context): ?string
                {
                    return $path === 'child.crv' ? $this->content : null;
                }
            };
            $renderer = new CarveRenderer(['includeResolver' => $uncached, 'cache' => ['config' => 'default', 'prefix' => 'uncached_' . bin2hex(random_bytes(4)) . '_']]);
            self::assertStringContainsString('First', $renderer->renderFile($path)->html());
            $uncached->content = 'Second';
            self::assertStringContainsString('Second', $renderer->renderFile($path)->html());
        } finally {
            unlink($path);
        }
    }

    public function testFileReportAccessorsAndIncludeAwareLint(): void
    {
        $root = sys_get_temp_dir() . '/carve-lint-' . bin2hex(random_bytes(5));
        mkdir($root);
        file_put_contents($root . '/main.crv', "{{ child.crv }}\n");
        file_put_contents($root . '/child.crv', "**bold**\n");
        try {
            $renderer = new CarveRenderer(['includeRoot' => $root]);
            $result = $renderer->renderFile($root . '/main.crv');
            self::assertNotEmpty($result->dependencies());
            self::assertSame(0, $result->suppressedWarnings());
            $findings = $renderer->lintFile($root . '/main.crv');
            self::assertNotEmpty($findings);
            self::assertSame($root . '/child.crv', $findings[0]['file']);
            self::assertSame($renderer->lint('**bold**')[0]['rule'], $findings[0]['rule']);
            self::assertSame($renderer->lint('**bold**')[0]['rule'], (new CarveRenderer())->lintFile($root . '/child.crv')[0]['rule']);
        } finally {
            unlink($root . '/main.crv');
            unlink($root . '/child.crv');
            rmdir($root);
        }
    }

    public function testLintFileUsesCustomResolver(): void
    {
        $path = tempnam(sys_get_temp_dir(), 'carve-lint-');
        self::assertIsString($path);
        file_put_contents($path, "{{ child.crv }}\n");
        try {
            $resolver = new class implements IncludeResolverInterface {
                public function resolve(string $path, IncludeContext $context): ?string
                {
                    return $path === 'child.crv' ? "**bold**\n" : null;
                }
            };
            $findings = (new CarveRenderer(['includeResolver' => $resolver]))->lintFile($path);
            self::assertNotEmpty($findings);
            self::assertSame('child.crv', $findings[0]['file']);
        } finally {
            unlink($path);
        }
    }
}
