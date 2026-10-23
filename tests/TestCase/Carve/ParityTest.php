<?php

declare(strict_types=1);

namespace MarkupCarve\CakeCarve\Test\TestCase\Carve;

use MarkupCarve\CakeCarve\Carve\CarveRenderer;
use MarkupCarve\CakeCarve\Carve\IncludeCacheKeyProvider;
use MarkupCarve\Carve\CarveConverter;
use MarkupCarve\Carve\Extension\TableOfContentsExtension;
use MarkupCarve\Carve\Renderer\AnsiRenderer;
use MarkupCarve\Carve\SafeMode;
use MarkupCarve\Carve\Transform\IncludeContext;
use MarkupCarve\Carve\Transform\IncludeResolverInterface;
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
}
