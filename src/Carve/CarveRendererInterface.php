<?php

declare(strict_types=1);

namespace MarkupCarve\CakeCarve\Carve;

interface CarveRendererInterface
{
    /**
     * @param string $source
     * @param array<string, mixed> $options
     */
    public function toHtml(string $source, array $options = []): string;

    /**
     * @param string $source
     * @param array<string, mixed> $options
     */
    public function render(string $source, array $options = []): RenderedCarve;

    /**
     * @param string $path
     * @param array<string, mixed> $options
     */
    public function renderFile(string $path, array $options = []): RenderedCarve;

    /**
     * @param string $source
     * @param array<string, mixed> $options
     */
    public function toText(string $source, array $options = []): string;

    /**
     * @param string $source
     * @param array<string, mixed> $options
     */
    public function toMarkdown(string $source, array $options = []): string;

    public function format(string $source): string;

    /**
     * @return array<array{rule: string, message: string, line: int, column: int}>
     */
    public function lint(string $source): array;

    public function import(string $source, string $from): ImportResult;

    /**
     * The effective options for a call: defaults, then `Carve` config, then the preset, then `$options`.
     *
     * @param array<string, mixed> $options
     *
     * @return array<string, mixed>
     */
    public function resolveOptions(array $options = []): array;
}
