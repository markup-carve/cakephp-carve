<?php

declare(strict_types=1);

namespace MarkupCarve\CakeCarve\TestSuite;

use MarkupCarve\CakeCarve\Carve\RendererFactory;
use MarkupCarve\CakeCarve\Validation\CarveValidation;

trait CarveAssertionsTrait
{
    /**
     * @param string $expected
     * @param string $source
     * @param array<string, mixed> $options
     */
    public function assertCarveHtml(string $expected, string $source, array $options = []): void
    {
        self::assertSame($expected, RendererFactory::create()->toHtml($source, $options));
    }

    /**
     * @param string $source
     * @param array<string, mixed> $options
     */
    public function assertCarveValid(string $source, array $options = []): void
    {
        self::assertSame(true, CarveValidation::valid($source, $options));
    }
}
