# Rendering and options

Create `new CarveRenderer()` and call `toHtml()`, `render()`, `renderFile()`, `toText()`, or `toMarkdown()`. `render()` returns HTML, headings, frontmatter, and warnings through `RenderedCarve`. `meta()` decodes JSON frontmatter out of the box; YAML needs `symfony/yaml` (or the `yaml` PHP extension) installed in the app, and yields no metadata without one. `format()` uses the engine formatter. `lint()` returns rule, message, line, and column for each finding. `import()` accepts `markdown`, `html`, `djot`, and `bbcode` and returns `ImportResult` with text in `value`, engine diagnostics in `diagnostics`, and degraded or dropped findings from `losses()`.

Pass an options array to a render method to override `Configure::read('Carve')`. `preset` selects a named option set. The built in presets are `default`, `comment` (strict safe mode and comment profile), and `trusted` (safe mode off). Set `safeMode` to `true`, `false`, or `'strict'`. Set `profile` to `full`, `article`, `comment`, `minimal`, or a `Profile` object. `onDisallowed` accepts `strip`, `to_text`, or `error`.

`labels` overrides the strings the engine writes itself rather than the author, such as the footnote backlink or the table-of-contents label, keyed as in carve-php's `HtmlRenderer::LABEL_DEFAULTS`. `roundTripMode` adds round-trip carriers to HTML and Markdown output. `renderers` maps an extension name (`mermaid`, `chart`, `math`) to a closure that turns its source into HTML at build time; it applies in `static` mode.

Options resolve in this order: the plugin defaults, the `Carve` configuration, the selected preset, then the per-call options. `resolveOptions($options)` returns the result of that merge, which is what validation uses to check the same profile the renderer applies.

`renderFile()` expands includes when `includeRoot` or a custom `includeResolver` is set. A relative `includeRoot` resolves against the current working directory, so prefer an absolute path. The filesystem resolver confines included files to its root. `lintFile($path, $options)` uses the same resolver selection and returns each finding with a `file` field. File render reports expose `dependencies()` and `suppressedWarnings()`.

The engine reports lint findings without severity. Validation uses a boolean `lint` option and optional rule ID filters.

`toAnsi($source, $options)` renders terminal output with the engine ANSI renderer. Set `rawHtml` to `strip`, `escape`, or `allow` to control raw HTML under `safeMode: true`. With `safeMode: 'strict'` or an explicit `SafeMode` instance, `rawHtml` is ignored and their own raw HTML handling applies. `allow` under `safeMode: true` passes raw HTML verbatim; use it only for trusted authors. The default, `null`, keeps the engine setting. Set `includeResolver` to an `IncludeResolverInterface` instance or class name to resolve includes through your own source. It takes precedence over `includeRoot`.

## Options reference

- `safeMode`: `true`, `false`, `'strict'`, or a `SafeMode` instance.
- `rawHtml`: `null`, `strip`, `escape`, or `allow`; applies only under `safeMode: true`.
- `profile`: `null`, `full`, `article`, `comment`, `minimal`, or a `Profile` instance.
- `onDisallowed`: `strip`, `to_text`, or `error`.
- `softBreakMode`: `null`, `newline`, `space`, or `br`.
- `smartTypography`: `null`, a boolean, or `SmartTypographyMode` (`glyph`, `source`, `quotes-source`).
- `sourceLines`: boolean; add source line attributes to rendered HTML.
- `symbols`: map of symbol names to replacement strings.
- `xhtml`: boolean; render XHTML style empty tags.
- `mode`: `interactive` or `static`.
- `strict`: boolean; throw on parse errors.
- `warnings`: boolean; collect parse warnings in `RenderedCarve`.
- `labels`: map of engine UI label names to strings.
- `roundTripMode`: boolean; add round trip carriers to HTML and Markdown.
- `renderers`: map of extension names to build time renderer closures.
- `includeRoot`: `null` or a filesystem root path.
- `includeResolver`: `null`, an `IncludeResolverInterface` instance, or its class name.
- `extensions`: array of extension names, specs, classes, or instances.
- `preset`: name of a configured preset; defaults to `default`.
- `presets`: map of preset names to option maps.
- `cache`: `false` or a map with `config`, `prefix`, and optional `version`.
- `debug`: `null` or boolean; controls the helper timing comment.
