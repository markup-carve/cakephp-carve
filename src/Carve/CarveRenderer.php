<?php

declare(strict_types=1);

namespace MarkupCarve\CakeCarve\Carve;

use Cake\Cache\Cache;
use Cake\Core\Configure;
use Closure;
use InvalidArgumentException;
use MarkupCarve\Carve\CarveConverter;
use MarkupCarve\Carve\Converter\BbcodeToCarve;
use MarkupCarve\Carve\Converter\DjotToCarve;
use MarkupCarve\Carve\Converter\HtmlToCarve;
use MarkupCarve\Carve\Converter\MarkdownToCarve;
use MarkupCarve\Carve\Exception\ParseWarning;
use MarkupCarve\Carve\Extension\ExtensionInterface;
use MarkupCarve\Carve\Extension\FrontmatterExtension;
use MarkupCarve\Carve\Extension\TableOfContentsExtension;
use MarkupCarve\Carve\Lint\DocumentLinter;
use MarkupCarve\Carve\Lint\IncludeLinter;
use MarkupCarve\Carve\Node\Document;
use MarkupCarve\Carve\Profile;
use MarkupCarve\Carve\Renderer\AnsiRenderer;
use MarkupCarve\Carve\Renderer\MarkdownRenderer;
use MarkupCarve\Carve\Renderer\PlainTextRenderer;
use MarkupCarve\Carve\Renderer\SmartTypographyMode;
use MarkupCarve\Carve\Renderer\SoftBreakMode;
use MarkupCarve\Carve\SafeMode;
use MarkupCarve\Carve\Transform\FilesystemIncludeResolver;
use MarkupCarve\Carve\Transform\IncludeExpander;
use MarkupCarve\Carve\Transform\IncludeResolverInterface;
use RuntimeException;
use Throwable;
use UnitEnum;

class CarveRenderer implements CarveRendererInterface
{
    /**
     * @var array<string, mixed>
     */
    private array $config;

    /**
     * @var array<string, \MarkupCarve\Carve\CarveConverter>
     */
    private array $converters = [];

    /**
     * @var array<string, string>
     */
    private array $converterKeys = [];

    /**
     * @param array<string, mixed> $config
     */
    public function __construct(array $config = [])
    {
        $defaults = [
            'safeMode' => true,
            'rawHtml' => null,
            'xhtml' => false,
            'strict' => false,
            'warnings' => false,
            'profile' => null,
            'onDisallowed' => 'strip',
            'mode' => 'interactive',
            'softBreakMode' => null,
            'smartTypography' => null,
            'symbols' => [],
            'labels' => [],
            'sourceLines' => false,
            'roundTripMode' => false,
            'renderers' => [],
            'includeRoot' => null,
            'includeResolver' => null,
            'extensions' => [],
            'cache' => false,
            'presets' => [
                'default' => [],
                'comment' => ['safeMode' => 'strict', 'profile' => 'comment'],
                'trusted' => ['safeMode' => false],
            ],
        ];
        $global = Configure::read('Carve');
        $global = is_array($global) ? $global : [];
        $this->config = array_replace_recursive($defaults, $global, $config);
        foreach ([$global, $config] as $override) {
            if (!isset($override['presets']) || !is_array($override['presets'])) {
                continue;
            }
            foreach ($override['presets'] as $name => $preset) {
                if (is_array($preset) && array_key_exists('extensions', $preset)) {
                    $this->config['presets'][$name]['extensions'] = $preset['extensions'];
                }
            }
        }
        if (array_key_exists('extensions', $global)) {
            $this->config['extensions'] = $global['extensions'];
        }
        if (array_key_exists('extensions', $config)) {
            $this->config['extensions'] = $config['extensions'];
        }
    }

    /**
     * @param string $source
     * @param array<string, mixed> $options
     */
    public function toHtml(string $source, array $options = []): string
    {
        return $this->render($source, $options)->html();
    }

    /**
     * @param string $source
     * @param array<string, mixed> $options
     */
    public function render(string $source, array $options = []): RenderedCarve
    {
        $options = $this->options($options);
        $cacheKey = $this->cacheKey('string', $source, $options);
        if ($cacheKey !== null) {
            $cached = Cache::read($cacheKey, $this->cacheConfig($options));
            if ($cached instanceof RenderedCarve) {
                return $cached;
            }
        }
        $result = $this->renderSource($source, $options);
        if ($cacheKey !== null) {
            Cache::write($cacheKey, $result, $this->cacheConfig($options));
        }

        return $result;
    }

    /**
     * @param string $path
     * @param array<string, mixed> $options
     *
     * @throws \InvalidArgumentException
     * @throws \RuntimeException
     */
    public function renderFile(string $path, array $options = []): RenderedCarve
    {
        $real = realpath($path);
        if ($real === false || !is_file($real) || !is_readable($real)) {
            throw new InvalidArgumentException('Unreadable Carve file: ' . $path);
        }
        $source = file_get_contents($real);
        if ($source === false) {
            throw new RuntimeException('Unable to read Carve file: ' . $path);
        }
        $effective = $this->options($options);
        if ($effective['includeRoot'] === null && $effective['includeResolver'] === null) {
            return $this->render($source, $options);
        }
        [$resolver, $root] = $this->includeResolver($effective, $real);
        $converter = $this->converter('html', $effective);
        $expander = new IncludeExpander($resolver, $real, source: $source, extensions: $converter->getExtensions());
        $document = $converter->transform($converter->parse($source), $expander);
        $warnings = $this->bool($effective, 'warnings')
            ? array_map(self::warning(...), $converter->getWarnings())
            : [];
        $warnings = array_merge($warnings, array_map(self::warning(...), $expander->getWarnings()));
        $root = $root === null ? null : rtrim($root, DIRECTORY_SEPARATOR);
        $dependencies = array_map(static function ($dependency) use ($root): array {
            $target = $dependency->getTarget();
            $relative = $root === null ? $target : (str_starts_with($target, $root . DIRECTORY_SEPARATOR)
                ? substr($target, strlen($root) + 1)
                : '[outside-root]');

            return ['path' => $relative, 'resolved' => $dependency->isResolved()];
        }, $expander->getDependencies());

        $states = [];
        foreach ($expander->getDependencies() as $dependency) {
            $target = $dependency->getTarget();
            $states[$target] = $root !== null && str_starts_with($target, $root . DIRECTORY_SEPARATOR) && is_file($target)
                ? hash_file('sha256', $target)
                : null;
        }
        if ($root === null) {
            $states = $resolver instanceof IncludeCacheKeyProvider ? [$resolver->includeCacheKey($dependencies)] : [];
        }
        $cacheKey = $this->cacheKey('file', serialize([$source, $real, $states]), $effective);
        if ($root === null && !$resolver instanceof IncludeCacheKeyProvider) {
            $cacheKey = null;
        }
        if ($cacheKey !== null) {
            $cached = Cache::read($cacheKey, $this->cacheConfig($effective));
            if ($cached instanceof RenderedCarve) {
                return $cached;
            }
        }
        $result = $this->renderDocument($source, $converter, $document, $warnings, $dependencies, $expander->getSuppressedWarnings());
        if ($cacheKey !== null) {
            Cache::write($cacheKey, $result, $this->cacheConfig($effective));
        }

        return $result;
    }

    /**
     * @param string $source
     * @param array<string, mixed> $options
     */
    public function toText(string $source, array $options = []): string
    {
        $converter = $this->converter('text', $this->options($options));

        return $converter->convert($source);
    }

    /**
     * @param string $source
     * @param array<string, mixed> $options
     */
    public function toMarkdown(string $source, array $options = []): string
    {
        $converter = $this->converter('markdown', $this->options($options));

        return $converter->convert($source);
    }

    public function toAnsi(string $source, array $options = []): string
    {
        return $this->converter('ansi', $this->options($options))->convert($source);
    }

    /**
     * @param string $path
     * @param string $format
     * @param array<string, mixed> $options
     *
     * @throws \InvalidArgumentException
     * @throws \RuntimeException
     */
    public function renderFileAs(string $path, string $format, array $options = []): string
    {
        if ($format === 'html') {
            return $this->renderFile($path, $options)->html();
        }
        if (!in_array($format, ['text', 'markdown', 'ansi'], true)) {
            throw new InvalidArgumentException('Unknown Carve render format: ' . $format);
        }
        $real = realpath($path);
        if ($real === false || !is_file($real) || !is_readable($real)) {
            throw new InvalidArgumentException('Unreadable Carve file: ' . $path);
        }
        $source = file_get_contents($real);
        if ($source === false) {
            throw new RuntimeException('Unable to read Carve file: ' . $path);
        }
        $effective = $this->options($options);
        if ($effective['includeRoot'] === null && $effective['includeResolver'] === null) {
            return $this->converter($format, $effective)->convert($source);
        }
        [$resolver] = $this->includeResolver($effective, $real);
        $converter = $this->converter($format, $effective);
        $expander = new IncludeExpander($resolver, $real, source: $source, extensions: $converter->getExtensions());

        return $converter->render($converter->transform($converter->parse($source), $expander));
    }

    /**
     * The configured resolver, or the filesystem one confined to `includeRoot`; the root is null for a custom resolver.
     *
     * @param array<string, mixed> $effective
     * @param string $real
     *
     * @throws \InvalidArgumentException
     *
     * @return array{\MarkupCarve\Carve\Transform\IncludeResolverInterface, string|null}
     */
    private function includeResolver(array $effective, string $real): array
    {
        if ($effective['includeResolver'] !== null) {
            $resolver = $effective['includeResolver'];
            if (is_string($resolver) && is_subclass_of($resolver, IncludeResolverInterface::class)) {
                $resolver = new $resolver();
            }
            if (!$resolver instanceof IncludeResolverInterface) {
                throw new InvalidArgumentException('Invalid Carve includeResolver.');
            }

            return [$resolver, null];
        }
        $root = realpath($this->string($effective, 'includeRoot'));
        if ($root === false || !str_starts_with($real, rtrim($root, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR)) {
            throw new InvalidArgumentException('Carve source must be inside includeRoot.');
        }

        return [new FilesystemIncludeResolver($root), $root];
    }

    public function format(string $source, bool $alignTables = false): string
    {
        return CarveConverter::toCarve($source, alignTables: $alignTables);
    }

    /**
     * @return array<array{rule: string, message: string, line: int, column: int}>
     */
    public function lint(string $source): array
    {
        return array_map(static fn ($finding): array => [
            'rule' => $finding->rule,
            'message' => $finding->message,
            'line' => $finding->line,
            'column' => $finding->column,
        ], (new DocumentLinter())->lint($source));
    }

    /**
     * @param string $path
     * @param array<string, mixed> $options
     *
     * @throws \InvalidArgumentException
     * @throws \RuntimeException
     *
     * @return array<array{rule: string, message: string, line: int, column: int, file: string|null}>
     */
    public function lintFile(string $path, array $options = []): array
    {
        $real = realpath($path);
        if ($real === false || !is_file($real) || !is_readable($real)) {
            throw new InvalidArgumentException('Unreadable Carve file: ' . $path);
        }
        $source = file_get_contents($real);
        if ($source === false) {
            throw new RuntimeException('Unable to read Carve file: ' . $path);
        }
        $effective = $this->options($options);
        $resolver = null;
        if ($effective['includeRoot'] !== null || $effective['includeResolver'] !== null) {
            [$resolver] = $this->includeResolver($effective, $real);
        }
        $extensions = array_values(array_map(
            static fn ($spec): ExtensionInterface => (new ExtensionFactory())->create($spec),
            $this->extensions($effective),
        ));

        return array_map(static fn ($finding): array => [
            'rule' => $finding->rule,
            'message' => $finding->message,
            'line' => $finding->line,
            'column' => $finding->column,
            'file' => $finding->file,
        ], (new IncludeLinter())->lint($source, ['extensions' => $extensions], $resolver, $real));
    }

    public function import(string $source, string $from): ImportResult
    {
        $converter = match ($from) {
            'markdown' => new MarkdownToCarve(),
            'html' => new HtmlToCarve(),
            'djot' => new DjotToCarve(),
            'bbcode' => new BbcodeToCarve(),
            default => throw new InvalidArgumentException('Unknown Carve import format: ' . $from),
        };

        $result = $converter->convertWithFidelityReport($source);

        return new ImportResult($result->value, $result->sourceFormat, $result->diagnostics, $result);
    }

    /**
     * @param array<string, mixed> $options
     *
     * @return array<string, mixed>
     */
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
        if (!is_array($presets)) {
            throw new InvalidArgumentException('Carve presets must be an array.');
        }
        if (!is_string($name) || !isset($presets[$name]) || !is_array($presets[$name])) {
            throw new InvalidArgumentException('Unknown Carve preset.');
        }
        unset($options['preset']);

        return array_replace($this->config, $presets[$name], $options);
    }

    /**
     * @param string $target
     * @param array<string, mixed> $options
     *
     * @throws \InvalidArgumentException
     */
    private function converter(string $target, array $options): CarveConverter
    {
        $key = hash('sha256', serialize($this->normalize($options)));
        if (isset($this->converters[$target]) && $this->converterKeys[$target] === $key) {
            return $this->converters[$target];
        }
        $profile = $options['profile'];
        if (is_string($profile)) {
            if (!in_array($profile, ['full', 'article', 'comment', 'minimal'], true)) {
                throw new InvalidArgumentException('Unknown Carve profile: ' . $profile);
            }
            $profile = Profile::{$profile}();
        }
        if ($profile !== null && !$profile instanceof Profile) {
            throw new InvalidArgumentException('Invalid Carve profile.');
        }
        if ($profile !== null) {
            $profile = (clone $profile)->onDisallowed($this->string($options, 'onDisallowed'));
        }
        $safeMode = $options['safeMode'] === 'strict' ? SafeMode::strict() : $options['safeMode'];
        if (!is_bool($safeMode) && !$safeMode instanceof SafeMode) {
            throw new InvalidArgumentException('Invalid Carve safeMode.');
        }
        $rawHtml = $options['rawHtml'];
        if ($rawHtml !== null && !in_array($rawHtml, [SafeMode::RAW_HTML_STRIP, SafeMode::RAW_HTML_ESCAPE, SafeMode::RAW_HTML_ALLOW], true)) {
            throw new InvalidArgumentException('Invalid Carve rawHtml.');
        }
        // Only plain safe mode is tunable; 'strict' and an explicit SafeMode keep their own raw handling.
        if ($rawHtml !== null && $options['safeMode'] === true) {
            $safeMode = (new SafeMode())->setRawHtmlMode($rawHtml);
        }
        $renderer = match ($target) {
            'text' => new PlainTextRenderer(),
            'markdown' => new MarkdownRenderer(),
            'ansi' => new AnsiRenderer(),
            default => null,
        };
        $converter = new CarveConverter(
            xhtml: $this->bool($options, 'xhtml'),
            warnings: $this->bool($options, 'warnings'),
            strict: $this->bool($options, 'strict'),
            safeMode: $safeMode,
            profile: $profile,
            softBreakMode: $options['softBreakMode'] === null ? null : SoftBreakMode::from($this->string($options, 'softBreakMode')),
            smartTypography: $this->smartTypography($options),
            mode: $this->string($options, 'mode'),
            symbols: $this->stringMap($options, 'symbols'),
            renderer: $renderer,
            sourceLines: $this->bool($options, 'sourceLines'),
            roundTripMode: $this->bool($options, 'roundTripMode'),
            renderers: $this->renderers($options),
            labels: $this->stringMap($options, 'labels'),
        );
        $hasToc = false;
        foreach ($this->extensions($options) as $spec) {
            $extension = (new ExtensionFactory())->create($spec);
            $hasToc = $hasToc || $extension instanceof TableOfContentsExtension;
            $converter->addExtension($extension);
        }
        if ($target === 'html' && !$hasToc) {
            $converter->addExtension(new TableOfContentsExtension());
        }
        $this->converters[$target] = $converter;
        $this->converterKeys[$target] = $key;

        return $converter;
    }

    /**
     * @param string $source
     * @param array<string, mixed> $options
     */
    private function renderSource(string $source, array $options): RenderedCarve
    {
        $converter = $this->converter('html', $options);
        $document = $converter->parse($source);
        $warnings = $options['warnings'] ? array_map(self::warning(...), $converter->getWarnings()) : [];

        return $this->renderDocument($source, $converter, $document, $warnings);
    }

    /**
     * @param string $source
     * @param \MarkupCarve\Carve\CarveConverter $converter
     * @param \MarkupCarve\Carve\Node\Document $document
     * @param array<array{rule: string|null, message: string, line: int, column: int}> $warnings
     * @param array<array<string, mixed>> $dependencies
     * @param int $suppressed
     */
    private function renderDocument(
        string $source,
        CarveConverter $converter,
        Document $document,
        array $warnings = [],
        array $dependencies = [],
        int $suppressed = 0,
    ): RenderedCarve {
        $html = $converter->render($document);
        $toc = [];
        $frontmatter = null;
        foreach ($converter->getExtensions() as $extension) {
            if ($extension instanceof TableOfContentsExtension) {
                $toc = $extension->getToc();
            }
            if ($extension instanceof FrontmatterExtension && $extension->hasFrontmatter()) {
                $frontmatter = ['format' => (string)$extension->getFormat(), 'content' => (string)$extension->getContent()];
            }
        }

        return new RenderedCarve($source, $html, $toc, $frontmatter, $warnings, $dependencies, $suppressed);
    }

    /**
     * @return array{rule: string|null, message: string, line: int, column: int}
     */
    private static function warning(ParseWarning $warning): array
    {
        return [
            'rule' => $warning->getRule(),
            'message' => $warning->getMessage(),
            'line' => $warning->getLine(),
            'column' => $warning->getColumn(),
        ];
    }

    /**
     * Cache is skipped for mutable extensions and renderers unless the application supplies a cache version.
     *
     * @param string $kind
     * @param string $source
     * @param array<string, mixed> $options
     *
     * @throws \InvalidArgumentException
     */
    private function cacheKey(string $kind, string $source, array $options): ?string
    {
        if (!is_array($options['cache'])) {
            return null;
        }
        $cache = $options['cache'] + ['config' => 'default', 'prefix' => 'carve_', 'version' => null];
        if ($cache['version'] !== null && !is_string($cache['version'])) {
            throw new InvalidArgumentException('Invalid Carve cache version.');
        }
        if ($cache['version'] === null) {
            foreach ($this->extensions($options) as $extension) {
                if ($extension instanceof ExtensionInterface || $this->containsClosure($extension)) {
                    return null;
                }
            }
            if ($this->renderers($options) !== []) {
                return null;
            }
        }
        $options['cache'] = false;
        if ($cache['version'] !== null) {
            $options['extensions'] = $this->versionedCacheValue($this->extensions($options));
            $options['renderers'] = $this->versionedCacheValue($this->renderers($options));
        }

        return $this->string($cache, 'prefix') . hash('sha256', serialize([CarveConverter::LIB_VERSION, $kind, $source, $cache['version'], $this->normalize($options)]));
    }

    /**
     * @param mixed $value
     *
     * @return mixed
     */
    private function versionedCacheValue(mixed $value): mixed
    {
        if ($value instanceof Closure) {
            return Closure::class;
        }
        if (is_array($value)) {
            return array_map($this->versionedCacheValue(...), $value);
        }

        return $value;
    }

    /**
     * @param array<string, mixed> $options
     */
    private function cacheConfig(array $options): string
    {
        $cache = $options['cache'];
        if (!is_array($cache)) {
            return 'default';
        }

        return isset($cache['config']) && is_string($cache['config']) ? $cache['config'] : 'default';
    }

    /**
     * @param array<string, mixed> $options
     * @param string $key
     *
     * @throws \InvalidArgumentException
     */
    private function string(array $options, string $key): string
    {
        if (!isset($options[$key]) || !is_string($options[$key])) {
            throw new InvalidArgumentException('Invalid Carve option: ' . $key);
        }

        return $options[$key];
    }

    /**
     * @param array<string, mixed> $options
     * @param string $key
     *
     * @throws \InvalidArgumentException
     */
    private function bool(array $options, string $key): bool
    {
        if (!isset($options[$key]) || !is_bool($options[$key])) {
            throw new InvalidArgumentException('Invalid Carve option: ' . $key);
        }

        return $options[$key];
    }

    /**
     * @param array<string, mixed> $options
     *
     * @throws \InvalidArgumentException
     */
    private function smartTypography(array $options): SmartTypographyMode|bool|null
    {
        $value = $options['smartTypography'] ?? null;
        if ($value === null || is_bool($value) || $value instanceof SmartTypographyMode) {
            return $value;
        }

        throw new InvalidArgumentException('Invalid Carve option: smartTypography');
    }

    /**
     * @param array<string, mixed> $options
     * @param string $key
     *
     * @throws \InvalidArgumentException
     *
     * @return array<string, string>
     */
    private function stringMap(array $options, string $key): array
    {
        $map = $options[$key] ?? null;
        if (!is_array($map)) {
            throw new InvalidArgumentException('Invalid Carve ' . $key . '.');
        }
        foreach ($map as $name => $value) {
            if (!is_string($name) || !is_string($value)) {
                throw new InvalidArgumentException('Invalid Carve ' . $key . '.');
            }
        }

        return $map;
    }

    /**
     * @param array<string, mixed> $options
     *
     * @throws \InvalidArgumentException
     *
     * @return array<string, \Closure(string): string>
     */
    private function renderers(array $options): array
    {
        $renderers = $options['renderers'] ?? null;
        if (!is_array($renderers)) {
            throw new InvalidArgumentException('Invalid Carve renderers.');
        }
        $valid = [];
        foreach ($renderers as $name => $renderer) {
            if (!is_string($name) || !$renderer instanceof Closure) {
                throw new InvalidArgumentException('Invalid Carve renderers.');
            }
            $valid[$name] = $renderer;
        }

        return $valid;
    }

    /**
     * @param array<string, mixed> $options
     *
     * @throws \InvalidArgumentException
     *
     * @return array<\MarkupCarve\Carve\Extension\ExtensionInterface|array<mixed, mixed>|string>
     */
    private function extensions(array $options): array
    {
        $extensions = $options['extensions'] ?? null;
        if (!is_array($extensions)) {
            throw new InvalidArgumentException('Invalid Carve extensions.');
        }
        foreach ($extensions as $extension) {
            if (!is_string($extension) && !is_array($extension) && !$extension instanceof ExtensionInterface) {
                throw new InvalidArgumentException('Invalid Carve extension.');
            }
        }

        return $extensions;
    }

    private function containsClosure(mixed $value): bool
    {
        if ($value instanceof Closure) {
            return true;
        }
        if (is_array($value)) {
            foreach ($value as $item) {
                if ($this->containsClosure($item)) {
                    return true;
                }
            }
        }

        return false;
    }

    /**
     * @param array<mixed, mixed> $options
     *
     * @return array<mixed, mixed>
     */
    private function normalize(array $options): array
    {
        ksort($options);
        foreach ($options as &$value) {
            if (is_array($value)) {
                $value = $this->normalize($value);
            } elseif ($value instanceof UnitEnum) {
                $value = $value::class . ':' . $value->name;
            } elseif ($value instanceof Closure) {
                $value = Closure::class . '#' . spl_object_id($value);
            } elseif (is_object($value)) {
                if ($value instanceof ExtensionInterface || $value instanceof Profile || $value instanceof SafeMode) {
                    try {
                        $value = serialize($value);
                    } catch (Throwable) {
                        $value = $value::class . '#' . spl_object_id($value);
                    }
                } else {
                    $value = $value::class;
                }
            }
        }

        return $options;
    }
}
