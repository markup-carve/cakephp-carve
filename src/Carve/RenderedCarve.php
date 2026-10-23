<?php

declare(strict_types=1);

namespace MarkupCarve\CakeCarve\Carve;

use JsonSerializable;
use Stringable;
use Symfony\Component\Yaml\Yaml;
use Throwable;

final class RenderedCarve implements JsonSerializable, Stringable
{
    /**
     * @param string $source
     * @param string $html
     * @param array<array<string, mixed>> $toc
     * @param array{format: string, content: string}|null $frontmatter
     * @param array<array{rule: string|null, message: string, line: int, column: int}> $warnings
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
     * @return array<array{rule: string|null, message: string, line: int, column: int}>
     */
    public function warnings(): array
    {
        return $this->warnings;
    }

    public function hasFrontmatter(): bool
    {
        return $this->frontmatter !== null;
    }

    /**
     * CakePHP ships no YAML parser, so YAML needs symfony/yaml or ext-yaml; without either it yields no metadata.
     *
     * @return array<mixed>
     */
    private static function decode(string $format, string $content): array
    {
        try {
            $data = match ($format) {
                'json' => json_decode($content, true, flags: JSON_THROW_ON_ERROR),
                'yaml', 'yml' => match (true) {
                    class_exists(Yaml::class) => Yaml::parse($content),
                    function_exists('yaml_parse') => yaml_parse($content),
                    default => null,
                },
                default => null,
            };
        } catch (Throwable) {
            return [];
        }

        return is_array($data) ? $data : [];
    }

    public function meta(?string $key = null, mixed $default = null): mixed
    {
        $meta = $this->frontmatter !== null ? self::decode($this->frontmatter['format'], $this->frontmatter['content']) : [];
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
