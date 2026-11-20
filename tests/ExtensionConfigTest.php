<?php

declare(strict_types=1);

namespace MarkupCarve\CakeCarve\Test;

use InvalidArgumentException;
use MarkupCarve\CakeCarve\Carve\CarveRenderer;
use MarkupCarve\CakeCarve\Carve\ExtensionFactory;
use MarkupCarve\Carve\Extension\ExtensionDefinition;
use MarkupCarve\Carve\Node;
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

    public function testHeadingLevelsDefaultsToOneLevel(): void
    {
        self::assertStringContainsString('<h2>Title</h2>', (new CarveRenderer(['extensions' => ['heading_levels']]))->toHtml("# Title\n"));
    }

    public function testHeadingIdsDoNotLeakIntoFormatOptions(): void
    {
        self::assertSame("= Title\n", (new CarveRenderer(['headingIds' => 'lower']))->format("# Title\n"));
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

    public function testDocumentTransformsFollowConfigurationOrder(): void
    {
        $defaults = ['type' => 'default_attributes', 'defaults' => ['link' => ['class' => 'marked']]];
        $before = (new CarveRenderer(['extensions' => [$defaults, 'heading_permalinks']]))->toHtml("# Hello\n");
        $after = (new CarveRenderer(['extensions' => ['heading_permalinks', $defaults]]))->toHtml("# Hello\n");
        self::assertStringNotContainsString('permalink marked', $before);
        self::assertStringContainsString('permalink marked', $after);
    }

    public function testTocCaptureRunsTransformsOnceAndIncludesHeadingNumbers(): void
    {
        $calls = 0;
        $extension = new ExtensionDefinition('test.once', '1', documentTransform: static function (Node $view) use (&$calls): Node {
            ++$calls;

            return $view;
        });
        $result = (new CarveRenderer(['rawHtml' => true, 'sections' => false, 'extensions' => [$extension, 'heading_numbers']]))->render("# Hello\n");
        self::assertSame(1, $calls);
        self::assertSame('1 Hello', $result->toc()[0]['text']);
        self::assertStringNotContainsString('data-carve-host-toc', $result->html());
        $raw = (new CarveRenderer(['rawHtml' => true]))->render("`<div data-carve-host-toc=\"42\">x</div>`{=html}\n");
        self::assertStringContainsString('data-carve-host-toc="42"', $raw->html());
        foreach ([true, false] as $sections) {
            $wrapped = new CarveRenderer(['rawHtml' => true, 'headingIds' => 'off', 'sections' => $sections]);
            self::assertNull($wrapped->render("`<section id=\"outer\">`{=html}\n\n# Hi\n")->toc()[0]['id']);
        }
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
        $custom = new CarveRenderer(['extensions' => [['type' => 'heading_permalinks', 'css_class' => 'anchor-link']]]);
        self::assertSame('Hello', $custom->render("# Hello\n")->toc()[0]['text']);
        $trusted = new CarveRenderer(['rawHtml' => true, 'extensions' => ['heading_level_shift']]);
        $toc = $trusted->render("`<h2 id=\"manual\">Manual</h2>`{=html}\n\n# Real\n")->toc();
        self::assertSame('Real', $toc[0]['text']);
        self::assertSame('Real', $toc[0]['id']);
        $duplicateText = new CarveRenderer(['rawHtml' => true, 'headingIds' => 'lower', 'extensions' => ['heading_level_shift']]);
        $duplicateToc = $duplicateText->render("`<h2 id=\"manual\">My Title</h2>`{=html}\n\n# My Title\n")->toc();
        self::assertSame('my-title', $duplicateToc[0]['id']);
        $noIds = new CarveRenderer(['rawHtml' => true, 'headingIds' => 'off', 'extensions' => ['heading_level_shift']]);
        $noIdsToc = $noIds->render("`<h3 id=\"raw\">Real preview</h3>`{=html}\n\n# Real\n")->toc();
        self::assertSame(2, $noIdsToc[0]['level']);
        self::assertNull($noIdsToc[0]['id']);
        $noSections = new CarveRenderer(['rawHtml' => true, 'sections' => false, 'headingIds' => 'off', 'extensions' => ['heading_level_shift']]);
        $noSectionsToc = $noSections->render("`<h3 id=\"raw\">Real preview</h3>`{=html}\n\n# Real\n")->toc();
        self::assertSame(2, $noSectionsToc[0]['level']);
        self::assertNull($noSectionsToc[0]['id']);
        $dataId = new CarveRenderer(['extensions' => [['type' => 'default_attributes', 'defaults' => ['heading' => ['data-id' => 'wrong']]]]]);
        self::assertSame('Real', $dataId->render("# Real\n")->toc()[0]['id']);
    }
}
