# Extensions in Carve 0.2

The 0.2 extension API takes `ExtensionDefinition`, `ExtensionProvider`, or names and option arrays in `extensions`. Names map to carve-php bundle factories. For example, `heading_permalinks`, `autolink`, `table_of_contents`, `wikilinks`, `citations`, and `default_attributes` run through the engine.

```php
$renderer = new \MarkupCarve\CakeCarve\Carve\CarveRenderer([
    'extensions' => [$provider],
]);
```

Use `ExtensionFactory::types()` for the supported names. Array entries use a `type` and snake case factory options, for example `['type' => 'wikilinks', 'url_template' => '/wiki/{page}']`. Unknown options fail. Bundle document transforms run during HTML rendering. Configure the `social` engine option for mentions; the old `mentions` name reports an error.
