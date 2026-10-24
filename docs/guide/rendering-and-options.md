# Rendering and options

Use `CarveRenderer` when you need Carve output in PHP outside a template.

## Your first render

```php
use MarkupCarve\CakeCarve\Carve\CarveRenderer;

$renderer = new CarveRenderer();
echo $renderer->toHtml('Hello, *CakePHP*.');
```

```html
<p>Hello, <strong>CakePHP</strong>.</p>
```

## Choose an output format

| Method | Returns | Use for |
| --- | --- | --- |
| `toHtml($source)` | HTML string | Web pages. |
| `render($source)` | `RenderedCarve` | HTML plus headings, metadata, and warnings. |
| `toText($source)` | Plain text string | Search or previews. |
| `toMarkdown($source)` | Markdown string | Export. |
| `toAnsi($source)` | ANSI string | Terminal display. |
| `renderFileAs($path, $format)` | String | Render a file as `html`, `text`, `markdown`, or `ansi`. |

## Read the `RenderedCarve` result

```php
$result = $renderer->render('# Hello', ['warnings' => true]);
echo $result->html();
$headings = $result->toc();
$warnings = $result->warnings();
```

```html
<section id="Hello">
  <h1>Hello</h1>
</section>
```

`toc()` returns heading records with level, text, HTML, and anchor ID. `warnings()` returns parse findings when `warnings` is enabled. `hasFrontmatter()` reports whether frontmatter was found, and `meta()` decodes it. JSON works by default; YAML needs `symfony/yaml` or PHP's `yaml` extension.

```php
$result = $renderer->render("---json\n{\"title\":\"Hello\"}\n---\n# Hello\n", [
    'extensions' => ['frontmatter'],
]);
echo $result->meta('title'); // Hello
```

## Use presets and safe mode

The built-in presets are `default`, `comment`, and `trusted`. Use `comment` for visitor content. See [safe mode](safe-mode.md) for the trust rules.

```php
echo $renderer->toHtml('Hello, *CakePHP*.', ['preset' => 'comment']);
```

```html
<p>Hello, <strong>CakePHP</strong>.</p>
```

## Combine options

The merge order is renderer defaults, app `Carve` config, selected preset, then per-call options. The last value for a key wins. For example:

```php
$renderer = new CarveRenderer(['safeMode' => false]);
$options = $renderer->resolveOptions([
    'preset' => 'comment',
    'profile' => 'minimal',
]);
// safeMode is 'strict'; profile is 'minimal'.
```

Here the constructor sets `safeMode` to `false`; the `comment` preset changes it to `strict`; the call changes only `profile`.

## Render files and includes

```php
$result = $renderer->renderFile(ROOT . '/content/article.crv', [
    'includeRoot' => ROOT . '/content',
]);
echo $result->html();
```

`includeRoot` confines filesystem includes to that root. Use an absolute path. A custom `includeResolver` takes precedence. File results also expose `dependencies()` and `suppressedWarnings()`. `lintFile($path, $options)` uses the same resolver selection.

## Format, lint, and import

```php
$formatted = $renderer->format("Hello, *CakePHP*.\n");
$findings = $renderer->lint("Hello, *CakePHP*.\n");
$imported = $renderer->import('**bold**', 'markdown');
echo $imported->value;
```

`lint()` findings contain rule, message, line, and column. `import()` accepts `markdown`, `html`, `djot`, and `bbcode`; inspect `diagnostics` and `losses()` before replacing source files. See [commands](commands.md) for file workflows.

## Options reference

These defaults come from `CarveRenderer` and `config/app.example.php`.

| Option | Values | Default | What it does |
| --- | --- | --- | --- |
| `safeMode` | `true`, `false`, `strict`, `SafeMode` | `true` | Controls the engine's safe rendering policy. |
| `rawHtml` | `null`, `strip`, `escape`, `allow` | `null` | Handles explicit raw HTML under `safeMode: true`. |
| `xhtml` | Boolean | `false` | Emits XHTML style empty tags. |
| `strict` | Boolean | `false` | Throws on parse errors. |
| `warnings` | Boolean | `false` | Collects parse warnings in the result. |
| `profile` | `null`, `full`, `article`, `comment`, `minimal`, `Profile` | `null` | Limits allowed markup features. |
| `onDisallowed` | `strip`, `to_text`, `error` | `strip` | Removes, converts, or rejects disallowed markup. |
| `mode` | `interactive`, `static` | `interactive` | Selects interactive or build time rendering. |
| `softBreakMode` | `null`, `newline`, `space`, `br` | `null` | Keeps a source line break, turns it into a space, or emits `<br>`. |
| `smartTypography` | `null`, Boolean, `SmartTypographyMode` | `null` | Controls typographic substitutions. |
| `symbols` | Name to string map | `[]` | Replaces named symbols. |
| `sourceLines` | Boolean | `false` | Adds source line data to HTML. |
| `labels` | Name to string map | `[]` | Replaces engine UI labels, such as footnote backlink text. |
| `roundTripMode` | Boolean | `false` | Adds carriers for HTML or Markdown round trips. |
| `renderers` | Name to closure map | `[]` | Renders extension blocks at build time in static mode. |
| `includeRoot` | `null`, path | `null` | Sets the filesystem include boundary. |
| `includeResolver` | `null`, resolver object or class | `null` | Supplies a custom include source. |
| `extensions` | Names, specs, classes, instances | `[]` | Enables extra markup features. |
| `presets` | Name to option map | `default`, `comment`, `trusted` | Defines reusable option sets. |
| `preset` | Configured preset name | `default` | Selects an option set per call. |
| `cache` | `false`, config map | `false` | Caches render results with CakePHP Cache. |
| `debug` | `null`, Boolean | `null` | Controls helper timing comments; `null` follows app debug. |
