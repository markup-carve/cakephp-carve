# Configuration

Set plugin defaults under `Carve` in `config/app_local.php` or `config/app.php`. A render call can override most values. The defaults below match `config/app.example.php` and `CarveRenderer`.

## Set a default

```php
'Carve' => [
    'safeMode' => true,
    'profile' => 'comment',
],
```

## Find a key

| Key | Default | Meaning |
| --- | --- | --- |
| `rendererClass` | `CarveRenderer::class` | Renderer used by the helper and view; implement `CarveRendererInterface`. |
| `safeMode` | `true` | Engine safe rendering policy. |
| `rawHtml` | `null` | Raw HTML handling under normal safe mode. |
| `xhtml` | `false` | XHTML style empty tags. |
| `strict` | `false` | Throw on parse errors. |
| `warnings` | `false` | Collect parse warnings. |
| `profile` | `null` | Allowed markup profile. |
| `onDisallowed` | `strip` | Action for markup outside the profile. |
| `mode` | `interactive` | Interactive or static rendering. |
| `softBreakMode` | `null` | Rendering of soft line breaks. |
| `smartTypography` | `null` | Typography substitutions. |
| `symbols` | `[]` | Named symbol replacements. |
| `sourceLines` | `false` | Source line data in HTML. |
| `labels` | `[]` | Engine generated UI text. |
| `roundTripMode` | `false` | Carriers for round trip output. |
| `renderers` | `[]` | Build time extension renderers. |
| `includeRoot` | `null` | Filesystem include boundary. |
| `includeResolver` | `null` | Custom include source. |
| `extensions` | `[]` | Extra markup features. |
| `presets` | `default`, `comment`, `trusted` | Named option maps. |
| `cache` | `false` | CakePHP render cache settings. |
| `debug` | `null` | Helper timing comment; `null` follows app debug. |

See [rendering and options](rendering-and-options.md) for values and examples, [safe mode](safe-mode.md) for visitor content, [extensions](extensions.md) for extension names, and [caching](caching.md) for cache settings. JSON frontmatter metadata works by default; YAML needs `symfony/yaml` or PHP's `yaml` extension.
