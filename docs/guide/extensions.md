# Extensions in Carve 0.2

The 0.2 extension API takes `ExtensionDefinition` or `ExtensionProvider` instances in `extensions`. The `tabs` shorthand registers Carve's standard tabs definitions. Frontmatter, table of contents placement, autolinks, admonitions, and mentions are core syntax and their old names are accepted as harmless compatibility aliases.

```php
$renderer = new \MarkupCarve\CakeCarve\Carve\CarveRenderer([
    'extensions' => [$provider],
]);
```

Other 0.1 extension names and config arrays have no direct 0.2 definition yet. The plugin reports `Carve 0.2 extension <name> is not available yet` when one is configured. Register an equivalent 0.2 definition when it becomes available. The engine's inline extension renderers, static render channel, and document transforms are still provisional; the plugin does not route 0.1 callbacks through them.
