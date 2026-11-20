# Configuration

Copy the keys you use from [`config/app.example.php`](https://github.com/markup-carve/cakephp-carve/blob/next2/config/app.example.php) into your CakePHP configuration. The plugin's 0.2 options are documented in [rendering and options](rendering-and-options.md). Use `rawHtml` and `urlPolicy` for trust, `profile` for permitted markup, `includeRoot` or `includeResolver` for includes, `extensions` for Carve definitions or names, and `writers` for custom output targets.

The 0.1 keys `safeMode`, `xhtml`, `strict`, `warnings`, `onDisallowed`, `roundTripMode`, and `renderers` have no direct 0.2 mapping. Use `softBreak` for the engine's line break mode, `headingIds` for heading IDs, and `social` for mention and tag links. The plugin rejects unknown options so old security configuration cannot be mistaken for an active policy. A custom renderer implementing `CarveRendererInterface` also implements `textLength()` so validation measures included content.
