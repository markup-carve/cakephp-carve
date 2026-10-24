# Caching

Caching is off by default. Configure `'cache' => ['config' => 'default', 'prefix' => 'carve_']` to use CakePHP Cache. Keys include source, effective options, and the carve-php version. Cache is skipped for extension instances, `renderers`, or options containing closures, because their state cannot be expressed by a stable key. File cache keys include the source path and hashes of included files, so edits to an included file invalidate the result.

Set `cache.version` to an application managed string to cache extension instances, closure bearing specs, and `renderers`; change it when their behavior changes. A closure is keyed by its definition site and its captured values; when those values cannot be serialized, that render is not cached. A custom `includeResolver` skips file caching unless it implements `IncludeCacheKeyProvider::includeCacheKey()`. The provider key enters the file cache key.
