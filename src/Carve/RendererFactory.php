<?php

declare(strict_types=1);

namespace MarkupCarve\CakeCarve\Carve;

use Cake\Core\Configure;
use InvalidArgumentException;

final class RendererFactory
{
    /**
     * @param array<string, mixed> $config
     *
     * @throws \InvalidArgumentException
     */
    public static function create(array $config = []): CarveRendererInterface
    {
        $class = $config['className'] ?? Configure::read('Carve.className') ?? CarveRenderer::class;
        if (!is_string($class) || !is_a($class, CarveRendererInterface::class, true)) {
            throw new InvalidArgumentException('Invalid Carve renderer class.');
        }

        return new $class($config);
    }
}
