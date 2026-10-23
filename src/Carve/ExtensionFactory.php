<?php

declare(strict_types=1);

namespace MarkupCarve\CakeCarve\Carve;

use InvalidArgumentException;
use MarkupCarve\Carve\Extension\ExtensionInterface;
use ReflectionClass;
use Throwable;

final class ExtensionFactory
{
    /**
     * @param \MarkupCarve\Carve\Extension\ExtensionInterface|array<mixed, mixed>|string $spec
     */
    public function create(ExtensionInterface|array|string $spec): ExtensionInterface
    {
        if ($spec instanceof ExtensionInterface) {
            return $spec;
        }
        $config = is_string($spec) ? ['type' => $spec] : $spec;
        $type = $config['type'] ?? null;
        if (!is_string($type)) {
            throw new InvalidArgumentException('Carve extension type must be a string.');
        }
        unset($config['type']);
        if ($type === 'mermaid' || $type === 'plantuml') {
            $config += ['language' => $type === 'mermaid' ? 'mermaid' : ['plantuml', 'puml']];
            $type = 'fenced_render';
        }
        if ($type === 'fenced_render') {
            $config += ['language' => 'mermaid'];
        }
        if ($type === 'wikilinks' && isset($config['url_template'])) {
            if (!is_string($config['url_template'])) {
                throw new InvalidArgumentException('Wiki link URL template must be a string.');
            }
            $template = $config['url_template'];
            $config['url_generator'] = (new WikiLinkUrlTemplate($template))(...);
            unset($config['url_template']);
        }
        $aliases = match ($type) {
            'heading_permalinks' => ['class' => 'css_class'],
            'table_of_contents' => ['toc_class' => 'css_class'],
            'wikilinks' => ['link_class' => 'css_class'],
            'mentions' => ['user_url_template' => 'mention_url', 'user_class' => 'mention_class'],
            default => [],
        };
        $class = is_a($type, ExtensionInterface::class, true)
            ? $type
            : 'MarkupCarve\\Carve\\Extension\\' . str_replace(' ', '', ucwords(str_replace('_', ' ', $type))) . 'Extension';
        if (!is_a($class, ExtensionInterface::class, true)) {
            throw new InvalidArgumentException('Unknown Carve extension: ' . $type);
        }
        $arguments = [];
        foreach ($config as $key => $value) {
            $name = $aliases[$key] ?? $key;
            $arguments[lcfirst(str_replace(' ', '', ucwords(str_replace('_', ' ', $name))))] = $value;
        }
        $constructor = (new ReflectionClass($class))->getConstructor();
        $allowed = $constructor === null ? [] : array_map(
            static fn ($parameter): string => $parameter->getName(),
            $constructor->getParameters(),
        );
        foreach (array_keys($arguments) as $key) {
            if (!in_array($key, $allowed, true)) {
                throw new InvalidArgumentException('Unknown option ' . $key . ' for Carve extension ' . $type);
            }
        }
        try {
            return new $class(...$arguments);
        } catch (Throwable $exception) {
            throw new InvalidArgumentException('Invalid Carve extension ' . $type . ': ' . $exception->getMessage(), 0, $exception);
        }
    }
}
