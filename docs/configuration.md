# Configuration

Set values under `Carve` in the app's configuration. [app.example.php](../config/app.example.php) lists each setting with its default, type, and an example. Per-call options override app configuration. Set `rendererClass` to an implementation of `CarveRendererInterface` to replace the renderer used by the helper and view.

JSON frontmatter is parsed by `RenderedCarve::meta()`. YAML frontmatter needs `symfony/yaml` (preferred) or the PHP `yaml` extension. Unsupported or invalid frontmatter returns an empty array.
