# Configuration

Copy the keys you use from [`config/app.example.php`](https://github.com/markup-carve/cakephp-carve/blob/next2/config/app.example.php) into your CakePHP configuration. The plugin's 0.2 options are documented in [rendering and options](rendering-and-options.md). Use `rawHtml` and `urlPolicy` for trust, `profile` for permitted markup, `includeRoot` or `includeResolver` for includes, `extensions` for Carve2 definitions, and `writers` for custom output targets.

The 0.1 keys `safeMode`, `xhtml`, `strict`, `warnings`, `onDisallowed`, `softBreakMode`, `roundTripMode`, and `renderers` have no direct 0.2 mapping. Remove them when moving to this branch. The plugin rejects unknown options so old security configuration cannot be mistaken for an active policy. A custom renderer implementing `CarveRendererInterface` also implements `textLength()` so validation measures included content.
