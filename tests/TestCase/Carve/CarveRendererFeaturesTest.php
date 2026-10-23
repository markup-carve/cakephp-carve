<?php

declare(strict_types=1);

namespace MarkupCarve\CakeCarve\Test\TestCase\Carve;

use Cake\Cache\Cache;
use Cake\Core\Configure;
use MarkupCarve\CakeCarve\Carve\CarveRenderer;
use MarkupCarve\CakeCarve\Carve\ExtensionFactory;
use MarkupCarve\Carve\CarveConverter;
use MarkupCarve\Carve\Extension\TableOfContentsExtension;
use PHPUnit\Framework\TestCase;
use ReflectionMethod;

class CarveRendererFeaturesTest extends TestCase
{
    public function testExtensions(): void
    {
        $source = '# Heading';
        $toc = new TableOfContentsExtension();
        $direct = new CarveConverter(safeMode: true);
        $direct->addExtension($toc);
        self::assertSame($direct->convert($source), (new CarveRenderer(['extensions' => ['table_of_contents']]))->toHtml($source));
        self::assertSame($toc->getToc(), (new CarveRenderer(['extensions' => ['table_of_contents']]))->render($source)->toc());
        foreach (
            [
                ['heading_permalinks', '# Heading'],
                [['type' => 'mentions', 'user_url_template' => '/users/{user}'], '@alice'],
                [['type' => 'wikilinks', 'url_template' => '/wiki/{page}'], '[[Home]]'],
            ] as [$spec, $input]
        ) {
            $extension = (new ExtensionFactory())->create($spec);
            $engine = new CarveConverter(safeMode: true);
            $engine->addExtension($extension);
            self::assertSame($engine->convert($input), (new CarveRenderer(['extensions' => [$spec]]))->toHtml($input));
        }
        $custom = new TableOfContentsExtension();
        $engine = new CarveConverter(safeMode: true);
        $engine->addExtension(new TableOfContentsExtension());
        self::assertSame($engine->convert('# Custom'), (new CarveRenderer(['extensions' => [$custom]]))->toHtml('# Custom'));
        self::assertSame('Custom', $custom->getToc()[0]['text']);
    }

    public function testConstructorPresetReplacesExtensionList(): void
    {
        Configure::write('Carve.presets', ['blog' => ['extensions' => ['heading_permalinks', 'unknown_extension']]]);
        try {
            $renderer = new CarveRenderer(['presets' => ['blog' => ['extensions' => ['heading_permalinks']]]]);
            $html = $renderer->toHtml('# Heading', ['preset' => 'blog']);
            self::assertStringContainsString('permalink', $html);
        } finally {
            Configure::delete('Carve.presets');
        }
    }

    public function testCacheKeysAndUncacheableExtensions(): void
    {
        Cache::clear('default');
        $prefix = 'feature_' . bin2hex(random_bytes(4)) . '_';
        $renderer = new CarveRenderer(['cache' => ['config' => 'default', 'prefix' => $prefix]]);
        $source = '# One';
        $first = $renderer->render($source);
        $ref = new ReflectionMethod($renderer, 'cacheKey');
        $opts = new ReflectionMethod($renderer, 'options');
        $key = $ref->invoke($renderer, $source, $opts->invoke($renderer, []));
        self::assertIsString($key);
        self::assertSame($first, Cache::read($key, 'default'));
        self::assertSame($first, $renderer->render($source));
        $variants = [
            $ref->invoke($renderer, $source, $opts->invoke($renderer, ['xhtml' => true])),
            $ref->invoke($renderer, '# Two', $opts->invoke($renderer, [])),
            $ref->invoke($renderer, $source, $opts->invoke($renderer, ['preset' => 'trusted'])),
        ];
        $allKeys = [$key];
        foreach ($variants as $variant) {
            self::assertIsString($variant);
            $allKeys[] = $variant;
        }
        self::assertCount(4, array_unique($allKeys));
        self::assertNull($ref->invoke($renderer, $source, $opts->invoke($renderer, ['extensions' => [new TableOfContentsExtension()]])));
        self::assertNull($ref->invoke($renderer, $source, $opts->invoke($renderer, ['extensions' => [['type' => 'wikilinks', 'url_generator' => static fn (): string => '/wiki']]])));
    }
}
