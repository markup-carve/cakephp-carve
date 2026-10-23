# Changelog

## [Unreleased]

## [0.1.2] - 2026-10-23

- `rawHtml` applies only to `safeMode: true`; `'strict'` and an explicit `SafeMode` instance keep their own raw HTML handling, so `allow` can no longer loosen them.
- Render, convert, and format commands replace output files with checked atomic writes; they need write access to the target's directory, and a file owned by another user is refused rather than handed to the current user.
- Configured extensions run for text, Markdown, and ANSI output.
- `CarveBehavior` regenerates its text and TOC fields when one of them was changed directly, as it already did for the HTML field.
- Layout placeholder values cannot introduce a second `{{content}}` slot.
- Diagram presets `d2`, `graphviz`, `wavedrom`, `abc`, `vega_lite`, and `chart` are available by name.
- File render reports expose `dependencies()` and `suppressedWarnings()`.
- `CarveRenderer::lintFile()` lints includes with the configured resolver; `carve lint` uses it.
- `carve render --format html|text|markdown|ansi` and `CarveRenderer::toAnsi()`.
- `carve format --align-tables`; `format()` takes `$alignTables`.
- `CarveHelper::file()`, `lint()` and `format()`.
- Validation option `strict` fails on any parse warning and names them. It used to be passed to the engine as strict parsing, undocumented.
- `rawHtml` (`strip`, `escape`, `allow`) sets raw HTML handling under `safeMode: true`; the default stays the engine's.
- `cache.version` enters the cache key and allows caching extension instances, closures and `renderers`; a closure is keyed by where it is defined, so two different closures never share a cached result.
- `includeResolver` takes a custom include resolver; `IncludeCacheKeyProvider` lets it take part in the file cache.
- `CarveRendererInterface` gains `toAnsi()`, `renderFileAs()`, and `lintFile()`, and `format()` gains an optional `$alignTables`; custom renderers need to implement these additions.

## [0.1.1] - 2026-10-23

- `CarveBehavior` no longer saves a generated field set directly while its source field is not loaded; such a value is discarded instead of stored unrendered.
- File and string renders no longer share render cache keys.
- A relative `includeRoot` now expands includes instead of failing.
- `RenderedCarve::meta()` reads YAML frontmatter through `symfony/yaml` when installed; it used to need the `yaml` PHP extension and returned nothing without it.
- `labels`, `roundTripMode` and `renderers` options pass through to the engine.

## [0.1.0] - 2026-10-23

- Initial release of the CakePHP 5 Carve plugin.
- Add rendering, views, validation, behavior, commands, caching, and extensions.
