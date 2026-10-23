<?php

declare(strict_types=1);

namespace MarkupCarve\CakeCarve\Carve;

use JsonSerializable;
use Stringable;
use Throwable;

final class RenderedCarve implements JsonSerializable, Stringable
{
    /**
     * @param string $source
     * @param string $html
     * @param array<array<string, mixed>> $toc
     * @param array{format: string, content: string}|null $frontmatter
     * @param array<array<string, mixed>> $warnings
     * @param array<array<string, mixed>> $dependencies
     * @param int $suppressedWarnings
     */
    public function __construct(
        public readonly string $source,
        private readonly string $html,
        private readonly array $toc = [],
        private readonly ?array $frontmatter = null,
        private readonly array $warnings = [],
        private readonly array $dependencies = [],
        private readonly int $suppressedWarnings = 0,
    ) {
    }

    public function html(): string
    {
        return $this->html;
    }

    /**
     * @return array<array<string, mixed>>
     */
    public function toc(): array
    {
        return $this->toc;
    }

    /**
     * @return array<array<string, mixed>>
     */
    public function warnings(): array
    {
        return $this->warnings;
    }

    public function hasFrontmatter(): bool
    {
        return $this->frontmatter !== null;
    }

    public function meta(?string $key = null, mixed $default = null): mixed
    {
        $meta = [];
        if ($this->frontmatter !== null) {
            try {
                $content = $this->frontmatter['content'];
                $format = $this->frontmatter['format'];
                $parsed = match ($format) {
                    'json' => json_decode($content, true, flags: JSON_THROW_ON_ERROR),
                    'yaml', 'yml' => function_exists('yaml_parse') ? yaml_parse($content) : null,
                    default => null,
                };
                $meta = is_array($parsed) ? $parsed : [];
            } catch (Throwable) {
                $meta = [];
            }
        }
        if ($key === null) {
            return $meta;
        }
        foreach (explode('.', $key) as $part) {
            if (!is_array($meta) || !array_key_exists($part, $meta)) {
                return $default;
            }
            $meta = $meta[$part];
        }

        return $meta;
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'source' => $this->source,
            'html' => $this->html,
            'toc' => $this->toc,
            'frontmatter' => $this->frontmatter,
            'warnings' => $this->warnings,
            'dependencies' => $this->dependencies,
            'suppressedWarnings' => $this->suppressedWarnings,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function jsonSerialize(): array
    {
        return $this->toArray();
    }

    public function __toString(): string
    {
        return $this->html;
    }
}
