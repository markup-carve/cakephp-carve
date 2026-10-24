# Cache rendered pages

Enable caching when the same Carve source is rendered often and its output rarely changes. Caching is off by default.

## Enable a CakePHP cache

Put this under `Carve` in your app config:

```php
'cache' => [
    'config' => 'default',
    'prefix' => 'carve_',
],
```

The cache key includes the source, effective options, and carve-php version. A different source or option set gets a different result. For files, the key also includes the path and hashes of included files, so editing an included file invalidates that result.

## Handle custom extensions and includes

Extension instances, `renderers`, and closure options may carry state the cache cannot identify. Those renders skip caching by default. Set `cache.version` to an app managed string to opt in, and change that string when extension behavior changes. Closures also need serializable captured values; otherwise that render still skips the cache.

A custom `includeResolver` skips file caching unless it implements `IncludeCacheKeyProvider::includeCacheKey()`. Return a key that changes when your included content changes.
