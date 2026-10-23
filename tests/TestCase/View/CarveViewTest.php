<?php

declare(strict_types=1);

namespace MarkupCarve\CakeCarve\Test\TestCase\View;

use Cake\Core\Configure;
use MarkupCarve\CakeCarve\Carve\CarveRenderer;
use MarkupCarve\CakeCarve\View\CarveView;
use PHPUnit\Framework\TestCase;
use ReflectionProperty;

class CarveViewTest extends TestCase
{
    public function testCrvTemplateAndVariables(): void
    {
        $root = sys_get_temp_dir() . '/cakecarve-view-' . bin2hex(random_bytes(5));
        mkdir($root);
        file_put_contents($root . '/welcome.crv', "# Welcome, {{name}}!\n");
        Configure::write('App.paths.templates', [$root . '/']);
        try {
            $view = new CarveView();
            $view->disableAutoLayout();
            $view->set('name', 'Alex');
            self::assertStringContainsString('<h1>Welcome, Alex!</h1>', $view->render('welcome'));
            $carve = $view->get('carve');
            self::assertIsArray($carve);
            self::assertIsArray($carve['toc']);
            self::assertIsArray($carve['toc'][0]);
            self::assertSame('Welcome, Alex!', $carve['toc'][0]['text']);
        } finally {
            unlink($root . '/welcome.crv');
            rmdir($root);
        }
    }

    public function testLayoutKeepsContentAndMetadata(): void
    {
        $root = sys_get_temp_dir() . '/cakecarve-layout-' . bin2hex(random_bytes(5));
        mkdir($root . '/layout', 0777, true);
        file_put_contents($root . '/article.crv', "---json\n{\"title\":\"Page\"}\n---\n\n# Body\n");
        file_put_contents($root . '/layout/default.crv', "# {{carve.meta.title}}\n\n{{content}}\n");
        Configure::write('App.paths.templates', [$root . '/']);
        try {
            $view = new CarveView(viewOptions: ['extensions' => ['frontmatter']]);
            $html = $view->render('article');
            self::assertStringContainsString('<h1>Page</h1>', $html);
            self::assertStringContainsString('<h1>Body</h1>', $html);
            $carve = $view->get('carve');
            self::assertIsArray($carve);
            self::assertIsArray($carve['meta']);
            self::assertSame('Page', $carve['meta']['title']);
        } finally {
            unlink($root . '/article.crv');
            unlink($root . '/layout/default.crv');
            rmdir($root . '/layout');
            rmdir($root);
        }
    }

    public function testSafeModeGlobalConfigAndExtension(): void
    {
        $root = sys_get_temp_dir() . '/cakecarve-seed-view-' . bin2hex(random_bytes(5));
        mkdir($root);
        file_put_contents($root . '/safe.crv', "# Test\n\nA [dangerous link](javascript:alert(1)).");
        Configure::write('App.paths.templates', [$root . '/']);
        try {
            $view = new CarveView();
            $view->disableAutoLayout();
            self::assertSame((new CarveRenderer())->toHtml((string)file_get_contents($root . '/safe.crv')), $view->render('safe'));
            self::assertStringNotContainsString('javascript:', $view->render('safe'));
            self::assertSame('.crv', (new ReflectionProperty($view, '_ext'))->getValue($view));
            Configure::write('Carve', ['safeMode' => false]);
            $globalView = new CarveView();
            self::assertFalse($globalView->getConfig('safeMode'));
        } finally {
            Configure::delete('Carve');
            unlink($root . '/safe.crv');
            rmdir($root);
        }
    }
}
