<?php

declare(strict_types=1);

namespace MarkupCarve\CakeCarve\Carve;

use Cake\Cache\Cache;
use Cake\Core\Configure;
use DOMDocument;
use DOMElement;
use InvalidArgumentException;
use MarkupCarve\Carve\Carve;
use MarkupCarve\Carve\Diagnostics\Diagnostic;
use MarkupCarve\Carve\Extension\Bundle;
use MarkupCarve\Carve\Extension\ExtensionDefinition;
use MarkupCarve\Carve\Extension\ExtensionProvider;
use MarkupCarve\Carve\Include\FileResolver;
use MarkupCarve\Carve\Include\IncludeResolver;
use MarkupCarve\Carve\Node;
use MarkupCarve\Carve\Output\OutputWriter;
use RuntimeException;

class CarveRenderer implements CarveRendererInterface
{
    /**
     * @var array<string, mixed>
     */
    private array $config;

    /**
     * @param array<string, mixed> $config
     */
    public function __construct(array $config = [])
    {
        $defaults = [
            'rawHtml' => false,
            'urlPolicy' => ['deny' => ['javascript', 'vbscript', 'data', 'file']],
            'profile' => 'full',
            'mode' => 'interactive',
            'sections' => true,
            'smartTypography' => true,
            'softBreak' => 'newline',
            'social' => [],
            'headingIds' => 'keep-case',
            'sourceLines' => false,
            'strictLosses' => false,
            'roundtrip' => false,
            'symbols' => [],
            'labels' => [],
            'extensions' => [],
            'writers' => [],
            'includes' => null,
            'includeRoot' => null,
            'includeResolver' => null,
            'cache' => false,
            'presets' => [
                'default' => [],
                'comment' => ['profile' => 'comment'],
                'trusted' => ['rawHtml' => true],
            ],
        ];
        $global = Configure::read('Carve');
        $global = is_array($global) ? $global : [];
        $this->config = array_replace_recursive($defaults, $global, $config);
        foreach ([$global, $config] as $override) {
            foreach (['extensions', 'writers', 'urlPolicy', 'includes'] as $key) {
                if (array_key_exists($key, $override)) {
                    $this->config[$key] = $override[$key];
                }
            }
        }
    }

    public function toHtml(string $source, array $options = []): string
    {
        return $this->render($source, $options)->html();
    }

    public function render(string $source, array $options = []): RenderedCarve
    {
        $effective = $this->options($options);
        $key = $this->cacheKey('string', $source, $effective);
        if ($key !== null) {
            $cached = Cache::read($key, $this->cacheConfig($effective));
            if ($cached instanceof RenderedCarve) {
                return $cached;
            }
        }
        $result = $this->renderSource($source, $effective);
        if ($key !== null) {
            Cache::write($key, $result, $this->cacheConfig($effective));
        }

        return $result;
    }

    public function renderFile(string $path, array $options = []): RenderedCarve
    {
        [$source, $real] = self::readFile($path);
        $effective = $this->options($options);
        $parseOptions = $this->engineOptions($effective, 'parse', $real);
        $tree = Carve::parse($source, $parseOptions);
        $dependencies = $this->dependencies($tree, $parseOptions);
        $states = [];
        if (isset($effective['includeRoot']) && is_string($effective['includeRoot'])) {
            $root = realpath($effective['includeRoot']);
            foreach (Carve::includeDependencies($tree) as $dependency) {
                if ($root !== false && $dependency['file'] !== null) {
                    $file = $root . '/' . ltrim($dependency['file'], '/');
                    $states[$file] = is_file($file) ? hash_file('sha256', $file) : null;
                }
            }
        }
        $resolver = $effective['includeResolver'] ?? $effective['includes'];
        if ($resolver instanceof IncludeCacheKeyProvider) {
            $legacyDependencies = array_map(static fn (array $item): array => [
                'path' => is_string($item['path'] ?? null) ? $item['path'] : '',
                'resolved' => ($item['resolved'] ?? false) === true,
            ], $dependencies);
            $states['resolver'] = $resolver->includeCacheKey($legacyDependencies);
        }
        $unresolved = false;
        foreach ($dependencies as $dependency) {
            if ($dependency['resolved'] !== true) {
                $unresolved = true;

                break;
            }
        }
        $key = ($unresolved || (isset($parseOptions['includes']) && $states === [])) ? null
            : $this->cacheKey('file', serialize([$source, $real, $states]), $effective);
        if ($key !== null) {
            $cached = Cache::read($key, $this->cacheConfig($effective));
            if ($cached instanceof RenderedCarve) {
                return $cached;
            }
        }
        $result = $this->renderSource($source, $effective, $real, $tree, $dependencies);
        if ($key !== null) {
            Cache::write($key, $result, $this->cacheConfig($effective));
        }

        return $result;
    }

    public function toText(string $source, array $options = []): string
    {
        return $this->writeTarget('text', $source, $this->options($options));
    }

    public function toMarkdown(string $source, array $options = []): string
    {
        return $this->writeTarget('markdown', $source, $this->options($options));
    }

    public function toAnsi(string $source, array $options = []): string
    {
        return $this->writeTarget('ansi', $source, $this->options($options));
    }

    public function textLength(string $source, array $options = []): int
    {
        $effective = $this->options($options);
        $tree = Carve::parse($source, $this->engineOptions($effective, 'parse'));

        return mb_strlen(trim(self::plainText($tree)));
    }

    public function renderFileAs(string $path, string $format, array $options = []): string
    {
        if ($format === 'html') {
            return $this->renderFile($path, $options)->html();
        }
        if (!in_array($format, ['text', 'markdown', 'ansi'], true)) {
            throw new InvalidArgumentException('Unknown Carve render format: ' . $format);
        }
        [$source, $real] = self::readFile($path);

        return $this->writeTarget($format, $source, $this->options($options), $real);
    }

    public function format(string $source, bool $alignTables = false): string
    {
        $options = $this->engineOptions($this->options([]), 'parse');
        unset($options['includes']);

        return Carve::format($source, $options + ['alignTables' => $alignTables]);
    }

    public function lint(string $source): array
    {
        $options = $this->engineOptions($this->options([]), 'parse');

        return array_map(static fn (Diagnostic $d): array => self::finding($d, $source), Carve::lint($source, $options));
    }

    public function lintFile(string $path, array $options = []): array
    {
        [$source, $real] = self::readFile($path);
        $effective = $this->options($options);
        $engine = $this->engineOptions($effective, 'parse', $real);
        $findings = isset($engine['includes'])
            ? array_merge(Carve::lint($source), Carve::lint($source, $engine))
            : Carve::lint($source, $engine);
        $unique = [];
        foreach ($findings as $finding) {
            $key = serialize([$finding->code, $finding->file, $finding->startOffset, $finding->endOffset]);
            $unique[$key] = $finding;
        }

        $root = is_string($effective['includeRoot'] ?? null) ? realpath($effective['includeRoot']) : false;

        return array_map(static function (Diagnostic $d) use ($source, $real, $root): array {
            $file = $d->file ?? $real;
            $onDisk = $root !== false && !str_starts_with($file, '/') ? $root . '/' . $file : $file;
            $text = $file === $real ? $source : (is_file($onDisk) ? (string)file_get_contents($onDisk) : '');

            return self::finding($d, $text) + ['file' => $file];
        }, array_values($unique));
    }

    public function import(string $source, string $from): ImportResult
    {
        if ($from === 'html') {
            $effective = $this->options([]);
            $result = Carve::importDocument($source, ['from' => 'html', 'rawHtml' => $effective['rawHtml']]);

            return new ImportResult($result->source, 'html', $result->diagnostics, $result);
        }
        // The 0.1 Markdown importer read GitHub Flavored Markdown; in 0.2 `markdown` is CommonMark only.
        $format = match ($from) {
            'markdown', 'gfm' => 'gfm',
            'djot' => 'djot',
            default => throw new InvalidArgumentException('Unknown Carve import format: ' . $from),
        };
        $result = Carve::importDocument($source, ['from' => $format]);

        return new ImportResult($result->source, $from, $result->diagnostics, $result);
    }

    public function resolveOptions(array $options = []): array
    {
        return $this->options($options);
    }

    /**
     * @param array<string, mixed> $options
     *
     * @throws \InvalidArgumentException
     *
     * @return array<string, mixed>
     */
    private function options(array $options): array
    {
        $name = $options['preset'] ?? 'default';
        $presets = $this->config['presets'];
        if (!is_array($presets) || !is_string($name) || !isset($presets[$name]) || !is_array($presets[$name])) {
            throw new InvalidArgumentException('Unknown Carve preset.');
        }
        unset($options['preset']);

        $effective = array_replace($this->config, $presets[$name], $options);
        $allowed = ['rendererClass', 'className', 'debug'];
        foreach (array_keys($effective) as $key) {
            if (!array_key_exists($key, $this->config) && !in_array($key, $allowed, true)) {
                throw new InvalidArgumentException('Unknown Carve 0.2 option: ' . $key);
            }
        }

        return $effective;
    }

    /**
     * @param array<string, mixed> $effective
     * @param string|null $file
     * @param string $stage
     *
     * @throws \InvalidArgumentException
     *
     * @return array<string, mixed>
     */
    private function engineOptions(array $effective, string $stage, ?string $file = null): array
    {
        $extensions = [];
        if (!is_array($effective['extensions'])) {
            throw new InvalidArgumentException('Invalid Carve extensions.');
        }
        $factory = new ExtensionFactory();
        foreach ($effective['extensions'] as $extension) {
            if (!is_string($extension) && !is_array($extension) && !$extension instanceof ExtensionDefinition && !$extension instanceof ExtensionProvider) {
                throw new InvalidArgumentException('Invalid Carve extension entry.');
            }
            if ($extension === 'frontmatter' || $extension === 'admonition') {
                continue;
            }
            if ($extension === 'mentions') {
                throw new InvalidArgumentException('Configure the Carve social option for mentions.');
            }
            array_push($extensions, ...$factory->create($extension));
        }
        $out = ['extensions' => $extensions];
        if ($stage === 'render') {
            foreach (['rawHtml', 'urlPolicy', 'profile', 'mode', 'sections', 'smartTypography', 'softBreak', 'social', 'headingIds', 'sourceLines', 'strictLosses', 'roundtrip', 'symbols', 'labels'] as $key) {
                $out[$key] = $effective[$key];
            }
        }
        $resolver = $effective['includeResolver'] ?? $effective['includes'];
        if ($file !== null && $resolver === null && $effective['includeRoot'] !== null) {
            if (!is_string($effective['includeRoot'])) {
                throw new InvalidArgumentException('Invalid Carve includeRoot.');
            }
            $root = realpath($effective['includeRoot']);
            if ($root === false || !str_starts_with($file, rtrim($root, '/') . '/')) {
                throw new InvalidArgumentException('Carve source must be inside includeRoot.');
            }
            $resolver = FileResolver::forRoot($root);
        }
        if ($resolver !== null) {
            if (!$resolver instanceof IncludeResolver && !is_callable($resolver)) {
                throw new InvalidArgumentException('Invalid Carve includeResolver.');
            }
            $out['includes'] = ['resolver' => $resolver, 'file' => $file];
        }

        return $out;
    }

    /**
     * @param string $source
     * @param array<string, mixed> $effective
     * @param \MarkupCarve\Carve\Node|null $tree
     * @param string|null $file
     * @param array<array<string, mixed>> $dependencies
     */
    private function renderSource(string $source, array $effective, ?string $file = null, ?Node $tree = null, array $dependencies = []): RenderedCarve
    {
        $options = $this->engineOptions($effective, 'render', $file);
        $transforms = [];
        foreach (is_array($options['extensions']) ? $options['extensions'] : [] as $extension) {
            if ($extension instanceof Bundle) {
                array_push($transforms, ...array_values($extension->transforms()));
            }
        }
        $rendered = Carve::renderWithDocumentTransforms($source, $transforms, $options);
        $tree ??= Carve::parse($source, $this->engineOptions($effective, 'parse', $file));
        $frontmatter = null;
        $toc = [];
        foreach ($tree->children ?? [] as $node) {
            if ($node->type === 'frontmatter') {
                $frontmatter = ['format' => is_string($node->f['format'] ?? null) ? $node->f['format'] : '', 'content' => is_string($node->f['value'] ?? null) ? $node->f['value'] : ''];
            }
        }
        $toc = self::renderedHeadings($rendered->html);
        if ($file !== null && $dependencies === []) {
            $dependencies = $this->dependencies($tree, $this->engineOptions($effective, 'parse', $file));
        }
        $warnings = array_map(static fn (Diagnostic $d): array => self::finding($d, $source), $rendered->diagnostics);

        return new RenderedCarve($source, $rendered->html, $toc, $frontmatter, $warnings, $dependencies);
    }

    /**
     * @param string $target
     * @param string $source
     * @param array<string, mixed> $effective
     * @param string|null $file
     *
     * @throws \InvalidArgumentException
     * @throws \RuntimeException
     */
    private function writeTarget(string $target, string $source, array $effective, ?string $file = null): string
    {
        $registry = Carve::writers();
        if (!is_array($effective['writers'])) {
            throw new InvalidArgumentException('Invalid Carve writers.');
        }
        foreach ($effective['writers'] as $writer) {
            if (!$writer instanceof OutputWriter) {
                throw new InvalidArgumentException('Invalid Carve output writer.');
            }
            $registry->register($writer);
        }
        if ($target === 'text' && !$registry->has('text')) {
            $target = 'plain';
        }
        if (!$registry->has($target)) {
            throw new RuntimeException('Carve 0.2 ' . $target . ' writer not available yet.');
        }
        $tree = Carve::parse($source, $this->engineOptions($effective, 'parse', $file));

        $writerOptions = $target === 'text'
            ? $this->engineOptions($effective, 'render', $file)
            : [
                'smartTypography' => $effective['smartTypography'],
                'strictLosses' => $effective['strictLosses'],
                'urlPolicy' => $effective['urlPolicy'],
            ];

        return Carve::writeTo($target, $tree, $writerOptions, $registry);
    }

    /**
     * @param \MarkupCarve\Carve\Node $tree
     * @param array<string, mixed> $options
     *
     * @return array<array<string, mixed>>
     */
    private function dependencies(Node $tree, array $options): array
    {
        return array_map(static fn (array $d): array => [
            'path' => $d['path'],
            'resolved' => $d['read'],
            'id' => $d['file'],
        ], Carve::includeDependencies($tree, $options));
    }

    /**
     * @return array<array{level: int, id: string|null, text: string}>
     */
    private static function renderedHeadings(string $html): array
    {
        $document = new DOMDocument();
        $document->loadHTML('<meta charset="utf-8"><body>' . $html . '</body>', LIBXML_NOERROR | LIBXML_NOWARNING | LIBXML_NONET);
        $toc = [];
        foreach ($document->getElementsByTagName('*') as $element) {
            if (preg_match('/^h([1-6])$/', $element->tagName, $match) !== 1) {
                continue;
            }
            $id = $element->getAttribute('id');
            for ($parent = $element->parentNode; $id === '' && $parent instanceof DOMElement; $parent = $parent->parentNode) {
                if ($parent->tagName === 'section') {
                    $id = $parent->getAttribute('id');
                }
            }
            $heading = $element->cloneNode(true);
            if ($heading instanceof DOMElement) {
                foreach (iterator_to_array($heading->getElementsByTagName('a')) as $anchor) {
                    if (str_contains(' ' . $anchor->getAttribute('class') . ' ', ' permalink ')) {
                        $anchor->parentNode?->removeChild($anchor);
                    }
                }
            }
            $toc[] = ['level' => (int)$match[1], 'id' => $id === '' ? null : $id, 'text' => trim($heading->textContent ?? '')];
        }

        return $toc;
    }

    public static function plainText(Node $node): string
    {
        $text = in_array($node->type, ['text', 'code', 'code_block'], true) && is_string($node->f['value'] ?? null)
            ? $node->f['value'] : '';
        if ($node->type === 'image' && is_string($node->f['alt'] ?? null)) {
            $text = $node->f['alt'];
        }
        if (in_array($node->type, ['soft_break', 'hard_break'], true)) {
            return "\n";
        }
        foreach ($node->children ?? [] as $index => $child) {
            if ($index > 0 && in_array($node->type, ['document', 'list', 'block_quote'], true)) {
                $text .= "\n";
            }
            $text .= self::plainText($child);
        }

        return $text;
    }

    /**
     * @return array{rule: string, message: string, line: int, column: int}
     */
    private static function finding(Diagnostic $diagnostic, string $source): array
    {
        $offset = max(0, $diagnostic->startOffset);
        $prefix = substr($source, 0, $offset);
        $line = substr_count($prefix, "\n") + 1;
        $previous = strrpos($prefix, "\n");
        $column = mb_strlen(substr($prefix, $previous === false ? 0 : $previous + 1)) + 1;

        return ['rule' => $diagnostic->code, 'message' => $diagnostic->message, 'line' => $line, 'column' => $column];
    }

    /**
     * @throws \InvalidArgumentException
     * @throws \RuntimeException
     *
     * @return array{string, string}
     */
    private static function readFile(string $path): array
    {
        $real = realpath($path);
        if ($real === false || !is_file($real) || !is_readable($real)) {
            throw new InvalidArgumentException('Unreadable Carve file: ' . $path);
        }
        $source = file_get_contents($real);
        if ($source === false) {
            throw new RuntimeException('Unable to read Carve file: ' . $path);
        }

        return [$source, $real];
    }

    /**
     * @param string $kind
     * @param string $source
     * @param array<string, mixed> $options
     *
     * @throws \InvalidArgumentException
     */
    private function cacheKey(string $kind, string $source, array $options): ?string
    {
        $cache = $options['cache'];
        if (!is_array($cache)) {
            return null;
        }
        foreach (['extensions', 'writers', 'includes', 'includeResolver'] as $name) {
            if ($options[$name] !== null && $options[$name] !== [] && !isset($cache['version'])) {
                return null;
            }
        }
        $keyOptions = $options;
        unset($keyOptions['cache'], $keyOptions['presets']);
        if (self::containsObject($keyOptions)) {
            return null;
        }
        $prefix = $cache['prefix'] ?? 'carve_';
        if (!is_string($prefix)) {
            throw new InvalidArgumentException('Invalid Carve cache prefix.');
        }

        return $prefix . hash('sha256', serialize([Carve::class, $kind, $source, $cache['version'] ?? null, $keyOptions]));
    }

    private static function containsObject(mixed $value): bool
    {
        if (is_object($value)) {
            return true;
        }
        if (is_array($value)) {
            foreach ($value as $item) {
                if (self::containsObject($item)) {
                    return true;
                }
            }
        }

        return false;
    }

    /**
     * @param array<string, mixed> $options
     *
     * @throws \InvalidArgumentException
     */
    private function cacheConfig(array $options): string
    {
        $config = is_array($options['cache']) ? ($options['cache']['config'] ?? 'default') : 'default';
        if (!is_string($config)) {
            throw new InvalidArgumentException('Invalid Carve cache config.');
        }

        return $config;
    }
}
