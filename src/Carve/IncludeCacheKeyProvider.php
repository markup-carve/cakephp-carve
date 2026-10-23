<?php

declare(strict_types=1);

namespace MarkupCarve\CakeCarve\Carve;

interface IncludeCacheKeyProvider
{
    /**
     * @param array<array{path: string, resolved: bool}> $dependencies
     */
    public function includeCacheKey(array $dependencies): string;
}
