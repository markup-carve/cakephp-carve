# Changelog

## [Unreleased]

- `carve render --format html|text|markdown|ansi` and `CarveRenderer::toAnsi()`.
- `carve format --align-tables`; `format()` takes `$alignTables`.
- `CarveHelper::file()`, `lint()` and `format()`.
- Validation option `strict` fails on any parse warning and names them. It used to be passed to the engine as strict parsing, undocumented.
- `rawHtml` (`strip`, `escape`, `allow`) sets raw HTML handling under safe mode; the default stays the engine's.
- `cache.version` enters the cache key and allows caching extension instances, closures and `renderers`.
- `includeResolver` takes a custom include resolver; `IncludeCacheKeyProvider` lets it take part in the file cache.
- `CarveRendererInterface` gains `toAnsi()` and `renderFileAs()`, and `format()` gains an optional `$alignTables`; custom renderers implementing it need all three.

## [0.1.1] - 2026-10-23

- `CarveBehavior` no longer saves a generated field set directly while its source field is not loaded; such a value is discarded instead of stored unrendered.
- File and string renders no longer share render cache keys.
- A relative `includeRoot` now expands includes instead of failing.
- `RenderedCarve::meta()` reads YAML frontmatter through `symfony/yaml` when installed; it used to need the `yaml` PHP extension and returned nothing without it.
- `labels`, `roundTripMode` and `renderers` options pass through to the engine.

## [0.1.0] - 2026-10-23

- Initial release of the CakePHP 5 Carve plugin.
- Add rendering, views, validation, behavior, commands, caching, and extensions.
