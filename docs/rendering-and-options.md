# Rendering and options

Create `new CarveRenderer()` and call `toHtml()`, `render()`, `renderFile()`, `toText()`, or `toMarkdown()`. `render()` returns HTML, headings, frontmatter, and warnings through `RenderedCarve`. `meta()` decodes JSON frontmatter out of the box; YAML needs `symfony/yaml` (or the `yaml` PHP extension) installed in the app, and yields no metadata without one. `format()` uses the engine formatter. `lint()` returns rule, message, line, and column for each finding. `import()` accepts `markdown`, `html`, `djot`, and `bbcode` and returns `ImportResult` with text in `value`, engine diagnostics in `diagnostics`, and degraded or dropped findings from `losses()`.

Pass an options array to a render method to override `Configure::read('Carve')`. `preset` selects a named option set. The built in presets are `default`, `comment` (strict safe mode and comment profile), and `trusted` (safe mode off). Set `safeMode` to `true`, `false`, or `'strict'`. Set `profile` to `full`, `article`, `comment`, `minimal`, or a `Profile` object. `onDisallowed` accepts `strip`, `to_text`, or `error`.

`labels` overrides the strings the engine writes itself rather than the author, such as the footnote backlink or the table-of-contents label, keyed as in carve-php's `HtmlRenderer::LABEL_DEFAULTS`. `roundTripMode` adds round-trip carriers to HTML and Markdown output. `renderers` maps an extension name (`mermaid`, `chart`, `math`) to a closure that turns its source into HTML at build time; it applies in `static` mode.

Options resolve in this order: the plugin defaults, the `Carve` configuration, the selected preset, then the per-call options. `resolveOptions($options)` returns the result of that merge, which is what validation uses to check the same profile the renderer applies.

`renderFile()` expands include directives only when `includeRoot` is set; a relative path resolves against the current working directory, so prefer an absolute one. Without a root, directives remain unexpanded. The engine confines included files to that root.

The engine reports lint findings without severity. Validation uses a boolean `lint` option and optional rule ID filters.
