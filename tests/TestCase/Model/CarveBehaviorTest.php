<?php

declare(strict_types=1);

namespace MarkupCarve\CakeCarve\Test\TestCase\Model;

use Cake\Database\Connection;
use Cake\Datasource\ConnectionManager;
use Cake\ORM\Table;
use Cake\ORM\TableRegistry;
use MarkupCarve\CakeCarve\Model\Behavior\CarveBehavior;
use PHPUnit\Framework\TestCase;

class CarveBehaviorTest extends TestCase
{
    public function testRendersDirtySourceIntoSQLiteTable(): void
    {
        ConnectionManager::setConfig('carve_test', [
            'className' => 'Cake\\Database\\Connection',
            'driver' => 'Cake\\Database\\Driver\\Sqlite',
            'database' => ':memory:',
        ]);
        $connection = ConnectionManager::get('carve_test');
        self::assertInstanceOf(Connection::class, $connection);
        $connection->execute('CREATE TABLE carve_posts (id INTEGER PRIMARY KEY, body TEXT, body_html TEXT, body_text TEXT)');
        $table = new Table(['table' => 'carve_posts', 'connection' => $connection]);
        $table->addBehavior('Carve', [

            'className' => CarveBehavior::class,
            'fields' => [
                'body' => ['target' => 'body_html', 'text' => 'body_text'],
            ],
        ]);
        $entity = $table->newEntity(['body' => 'Hello']);
        $table->saveOrFail($entity);
        $html = $entity->get('body_html');
        $text = $entity->get('body_text');
        self::assertIsString($html);
        self::assertIsString($text);
        self::assertSame('<p>Hello</p>', trim($html));
        self::assertSame('Hello', trim($text));
        $entity->set('body', null);
        $table->saveOrFail($entity);
        self::assertNull($entity->get('body_html'));
        self::assertNull($entity->get('body_text'));
        ConnectionManager::drop('carve_test');
        TableRegistry::getTableLocator()->clear();
    }

    public function testCleanDirtyEmptyPresetAndMissingField(): void
    {
        ConnectionManager::setConfig('carve_scenarios', [
            'className' => 'Cake\\Database\\Connection',
            'driver' => 'Cake\\Database\\Driver\\Sqlite',
            'database' => ':memory:',
        ]);
        $connection = ConnectionManager::get('carve_scenarios');
        self::assertInstanceOf(Connection::class, $connection);
        $connection->execute('CREATE TABLE carve_scenarios (id INTEGER PRIMARY KEY, body TEXT, body_html TEXT, body_text TEXT, body_toc TEXT)');
        $table = new Table(['table' => 'carve_scenarios', 'connection' => $connection]);
        $table->getSchema()->setColumnType('body_toc', 'json');
        $table->addBehavior('Carve', [
            'className' => CarveBehavior::class,
            'fields' => [
                'body' => ['target' => 'body_html', 'text' => 'body_text', 'toc' => 'body_toc', 'preset' => 'trusted'],
                'absent' => 'absent_html',
            ],
        ]);
        try {
            $entity = $table->newEntity(['body' => '`<b>raw</b>`{=html}']);
            $table->saveOrFail($entity);
            $html = $entity->get('body_html');
            self::assertIsString($html);
            self::assertStringContainsString('<b>raw</b>', $html);
            self::assertFalse($entity->has('absent_html'));
            $entity->set('body_html', 'Keep me');
            $entity->clean();
            $table->saveOrFail($entity);
            self::assertSame('Keep me', $entity->get('body_html'));
            $entity->set('body_html', '<script>hostile</script>');
            $table->saveOrFail($entity);
            $safeHtml = $entity->get('body_html');
            self::assertIsString($safeHtml);
            self::assertStringNotContainsString('<script>', $safeHtml);
            $entity->set('body_html', '');
            $table->saveOrFail($entity);
            $html = $entity->get('body_html');
            self::assertIsString($html);
            self::assertStringContainsString('<b>raw</b>', $html);
            $entity->set('body', '# Changed');
            $table->saveOrFail($entity);
            $html = $entity->get('body_html');
            self::assertIsString($html);
            self::assertStringContainsString('<h1>Changed</h1>', $html);
            $text = $entity->get('body_text');
            self::assertIsString($text);
            self::assertSame('Changed', trim($text));
            self::assertNotEmpty($entity->get('body_toc'));
        } finally {
            ConnectionManager::drop('carve_scenarios');
            TableRegistry::getTableLocator()->clear();
        }
    }
}
