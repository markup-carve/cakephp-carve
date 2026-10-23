# Rendering and options

Create `new CarveRenderer()` and call `toHtml()`, `render()`, `renderFile()`, `toText()`, or `toMarkdown()`. `render()` returns HTML, headings, frontmatter, and warnings through `RenderedCarve`. `format()` uses the engine formatter. `lint()` returns rule, message, line, and column for each finding. `import()` accepts `markdown`, `html`, `djot`, and `bbcode` and returns `ImportResult` with text in `value`, engine diagnostics in `diagnostics`, and degraded or dropped findings from `losses()`.

Pass an options array to a render method to override `Configure::read('Carve')`. `preset` selects a named option set. The built in presets are `default`, `comment` (strict safe mode and comment profile), and `trusted` (safe mode off). Set `safeMode` to `true`, `false`, or `'strict'`. Set `profile` to `full`, `article`, `comment`, `minimal`, or a `Profile` object. `onDisallowed` accepts `strip`, `to_text`, or `error`.

Options resolve in this order: the plugin defaults, the `Carve` configuration, the selected preset, then the per-call options. `resolveOptions($options)` returns the result of that merge, which is what validation uses to check the same profile the renderer applies.

`renderFile()` expands include directives only when `includeRoot` is an absolute path. Without a root, directives remain unexpanded. The engine confines included files to that root.

The engine reports lint findings without severity. Validation uses a boolean `lint` option and optional rule ID filters.
