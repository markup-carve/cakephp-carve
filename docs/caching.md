# Caching

Caching is off by default. Configure `'cache' => ['config' => 'default', 'prefix' => 'carve_']` to use CakePHP Cache. Keys include source, effective options, and the carve-php version. Cache is skipped for extension instances or options containing closures, because their state cannot be expressed by a stable key. File cache keys include the source path and hashes of included files, so edits to an included file invalidate the result.
