# Changelog

## [Unreleased]

## [0.1.1] - 2026-10-23

- `CarveBehavior` no longer saves a generated field set directly while its source field is not loaded; such a value is discarded instead of stored unrendered.
- File and string renders no longer share render cache keys.
- A relative `includeRoot` now expands includes instead of failing.
- `labels`, `roundTripMode` and `renderers` options pass through to the engine.

## [0.1.0] - 2026-10-23

- Initial release of the CakePHP 5 Carve plugin.
- Add rendering, views, validation, behavior, commands, caching, and extensions.
