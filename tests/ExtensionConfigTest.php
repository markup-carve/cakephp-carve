<?php

declare(strict_types=1);

namespace MarkupCarve\CakeCarve\Test;

use InvalidArgumentException;
use MarkupCarve\CakeCarve\Carve\CarveRenderer;
use MarkupCarve\CakeCarve\Carve\ExtensionFactory;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

final class ExtensionConfigTest extends TestCase
{
    /**
     * @return array<string, array{array<string, mixed>|string}>
     */
    public static function extensionNames(): array
    {
        $special = ['default_attributes' => ['type' => 'default_attributes', 'defaults' => ['paragraph' => ['class' => 'note']]], 'fenced_render' => ['type' => 'fenced_render', 'language' => 'custom']];
        $cases = [];
        foreach (ExtensionFactory::types() as $name) {
            $cases[$name] = [$special[$name] ?? $name];
        }

        return $cases;
    }

    /**
     * @param array<string, mixed>|string $entry
     */
    #[DataProvider('extensionNames')]
    public function testEveryRegisteredNameCanRender(array|string $entry): void
    {
        $factory = new ExtensionFactory();
        self::assertNotSame([], $factory->create($entry));
        self::assertNotSame('', (new CarveRenderer(['extensions' => [$entry]]))->toHtml('hello'));
    }

    public function testExternalLinksNofollowOption(): void
    {
        $entry = ['type' => 'external_links', 'nofollow' => true];
        self::assertStringContainsString('nofollow', (new CarveRenderer(['extensions' => [$entry]]))->toHtml('[x](https://example.com)'));
    }

    public function testUnknownOptionIsRejected(): void
    {
        $this->expectException(InvalidArgumentException::class);
        (new ExtensionFactory())->create(['type' => 'heading_permalinks', 'misspelled' => true]);
    }

    public function testBundleDocumentTransformsRun(): void
    {
        $entry = ['type' => 'default_attributes', 'defaults' => ['paragraph' => ['class' => 'note']]];
        $factory = new ExtensionFactory();
        self::assertStringContainsString('class="note"', (new CarveRenderer(['extensions' => [$entry]]))->toHtml('hello'));
    }

    public function testEngineRenderOptionsAndImporters(): void
    {
        $renderer = new CarveRenderer(['softBreak' => 'space', 'headingIds' => 'lower', 'social' => ['mentionUrl' => '/people/{name}']]);
        self::assertStringContainsString('a b', $renderer->toHtml("a\nb"));
        self::assertStringContainsString('id="title"', $renderer->toHtml("# Title\n"));
        self::assertStringContainsString('/people/alex', $renderer->toHtml('Hi @alex'));
        self::assertSame("= Title\n", $renderer->import('# Title', 'markdown')->value);
        self::assertSame("= Title\n", $renderer->import('# Title', 'djot')->value);
    }

    public function testTocUsesRenderedHeadingIdsAndLevels(): void
    {
        $renderer = new CarveRenderer(['headingIds' => 'lower', 'extensions' => ['heading_level_shift', 'heading_permalinks']]);
        self::assertSame(
            [['level' => 2, 'id' => 'my-title', 'text' => 'My Title']],
            $renderer->render("# My Title\n")->toc(),
        );
    }
}
