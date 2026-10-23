<?php

declare(strict_types=1);

namespace MarkupCarve\CakeCarve\Test\TestCase\View;

use Cake\Core\Plugin;
use Cake\View\View;
use MarkupCarve\CakeCarve\CakeCarvePlugin;
use MarkupCarve\CakeCarve\View\Helper\CarveHelper;
use MarkupCarve\Carve\CarveConverter;
use MarkupCarve\Carve\Renderer\MarkdownRenderer;
use MarkupCarve\Carve\Renderer\PlainTextRenderer;
use PHPUnit\Framework\TestCase;

class CarveHelperTest extends TestCase
{
    public function testLoadsThroughThePluginHelperName(): void
    {
        Plugin::getCollection()->add(new CakeCarvePlugin());
        try {
            $view = new View();
            $helper = $view->loadHelper('MarkupCarve/CakeCarve.Carve', ['debug' => false]);
            if (!$helper instanceof CarveHelper) {
                self::fail('The plugin helper name did not resolve to CarveHelper.');
            }
            self::assertSame('<p><strong>b</strong></p>', $helper->convert('*b*'));
        } finally {
            Plugin::getCollection()->remove('MarkupCarve/CakeCarve');
        }
    }

    public function testHelperMethods(): void
    {
        $helper = new CarveHelper(new View(), ['debug' => true]);
        self::assertStringContainsString('<p>Hello</p>', $helper->convert('Hello'));
        self::assertStringContainsString('ms -->', $helper->convert('Hello'));
        self::assertSame('Hello', $helper->text('Hello'));
        self::assertSame('Hello', $helper->markdown('Hello'));
        self::assertSame('Hello', $helper->toc('# Hello')[0]['text']);
    }

    public function testTextMarkdownQuoteAndDebug(): void
    {
        $helper = new CarveHelper(new View(), ['debug' => false]);
        $text = 'Some *bold* text with <unsafe>.';
        self::assertSame(trim((new CarveConverter(safeMode: true, renderer: new PlainTextRenderer()))->convert($text)), $helper->text($text));
        $source = "# Heading\n\nSome /italic/ text.";
        self::assertSame(trim((new CarveConverter(safeMode: true, renderer: new MarkdownRenderer()))->convert($source)), $helper->markdown($source));
        $quote = "Some\n\n> quoted text\n>\n> -- Benjamin Franklin\n\ntext.";
        self::assertSame(trim((new CarveConverter(safeMode: true))->convert($quote)), $helper->convert($quote));
        $helper->setConfig('debug', true);
        self::assertStringContainsString('ms -->', $helper->convert('Some *bold* text.'));
    }
}
