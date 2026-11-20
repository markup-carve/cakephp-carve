<?php

declare(strict_types=1);

namespace MarkupCarve\CakeCarve\Test;

use Cake\TestSuite\TestCase;
use MarkupCarve\CakeCarve\TestSuite\CarveAssertionsTrait;

final class CarveAssertionsTest extends TestCase
{
    use CarveAssertionsTrait;

    public function testAssertionsUseTheCurrentRenderer(): void
    {
        $this->assertCarveHtml('<p>Hello</p>', 'Hello');
        $this->assertCarveValid('Hello');
    }
}
