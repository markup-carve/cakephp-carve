# Rendering and options in Carve 0.2

The plugin uses the `MarkupCarve\Carve2\Carve2` engine. The Composer dependency is pinned to its `next2` commit until an engine release is available.

```php
use MarkupCarve\CakeCarve\Carve\CarveRenderer;

$renderer = new CarveRenderer();
echo $renderer->toHtml('Hello, *CakePHP*.');
```

```html
<p>Hello, <strong>CakePHP</strong>.</p>
```

`toHtml()` returns an HTML fragment. By default, headings render inside section elements. `render()` returns a `RenderedCarve` with `html()`, `toc()`, `warnings()`, `dependencies()`, `meta()`, and `hasFrontmatter()`. Frontmatter is core syntax in 0.2; it needs no extension registration. JSON metadata works directly, while YAML needs `symfony/yaml` or PHP's `yaml` extension.

```php
$result = $renderer->render("---json\n{\"title\":\"Hello\"}\n---\n\n# Hello\n");
echo $result->meta('title');
```

## Options

The merge order is defaults, application `Carve` config, selected preset, then options on the call. The engine validates its options. Old 0.1 option names are rejected instead of being silently ignored.

| Option | Values | Default | Purpose |
| --- | --- | --- | --- |
| `rawHtml` | Boolean | `false` | Allow explicit raw HTML when the source is trusted. |
| `urlPolicy` | Scheme deny or allow list | Denies dangerous schemes | Filter link and image URLs. |
| `profile` | `full`, `article`, `comment`, `minimal` | `full` | Limit supported markup. |
| `mode` | `interactive`, `static` | `interactive` | Select render mode. |
| `sections` | Boolean | `true` | Wrap headings and following content in sections. |
| `smartTypography` | Boolean | `true` | Enable typographic substitutions. |
| `sourceLines` | Boolean | `false` | Add source positions to HTML. |
| `strictLosses` | Boolean | `false` | Treat render losses as errors. |
| `roundtrip` | Boolean | `false` | Emit roundtrip carriers. |
| `symbols`, `labels` | Name to string map | Empty | Override symbols and UI labels. |
| `includeRoot` | Path or `null` | `null` | Confine file includes to an absolute root. |
| `includeResolver` | Carve2 resolver or callable | `null` | Supply custom include content. |
| `extensions` | Carve2 definitions or providers | Empty | Register 0.2 extensions. |
| `writers` | Carve2 output writer instances | Empty | Register output targets. |
| `cache` | `false` or Cake cache config map | `false` | Cache rendered results. |
| `presets`, `preset` | Named option maps, name | `default` | Reuse option sets. |

The `comment` preset selects the comment profile. The `trusted` preset enables raw HTML. Neither preset weakens the URL policy. Use `rawHtml` only for trusted authors.

## Files and includes

```php
$result = $renderer->renderFile(ROOT . '/content/article.crv', [
    'includeRoot' => ROOT . '/content',
]);
```

`includeRoot` must contain the entry file. A custom `includeResolver` takes precedence and implements Carve2's `IncludeResolver::resolve(array $request): array` or is a callable with the same signature. The result exposes each include path, resolved state, and dependency identity. `lintFile()` uses the same resolver configuration.

## Formatting, linting, and import

```php
$source = $renderer->format("a  \nb\n");
$findings = $renderer->lint("{#a}\nx\n\n{#a}\ny\n");
$imported = $renderer->import('<p>Hi <em>there</em></p>', 'html');
```

`format()` uses the 0.2 canonical source writer. `lint()` exposes the 0.2 diagnostic code as `rule`, with message, line, and column. HTML import returns canonical 0.2 source and diagnostics. The older Markdown, Djot, and BBCode imports remain named in the command but report that their 0.2 importer is unavailable.

## Output writers

The `toText()`, `toMarkdown()`, `toAnsi()`, helper, behavior, and CLI methods remain available. They select a writer from `Carve2::writers()` plus any instances in `writers`. The engine does not yet register these three writers, so an unregistered target raises `Carve 0.2 <target> writer not available yet`.

```php
$renderer = new CarveRenderer(['writers' => [$yourTextWriter]]);
echo $renderer->toText('Hello');
```

A writer implements `MarkupCarve\Carve2\Output\OutputWriter`. Registration is per renderer call, so one application's writer cannot change another application's registry.
