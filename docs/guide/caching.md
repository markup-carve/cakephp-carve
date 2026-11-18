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

The cache key includes the source, effective options, and Carve2 adapter version. A different source or option set gets a different result. For files, the key also includes the path and hashes of included files, so editing an included file invalidates that result.

## Handle custom extensions and includes

Extension definitions, output writer instances, and resolver callbacks can carry state the cache cannot identify. These renders skip caching by default. A custom include resolver with a stable `IncludeCacheKeyProvider` key can identify its content, but the adapter still avoids caching when an option contains an object.

A file rendered with `includeRoot` uses the hash of each resolved included file in its cache key. An unresolved include or a custom resolver without a stable cache identity skips file caching.
