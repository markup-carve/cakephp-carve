# Views and helper

Load the helper in `AppView::initialize()` with `$this->loadHelper('MarkupCarve/CakeCarve.Carve')`, then call `$this->Carve->convert($source)` for HTML. `render()` returns a `RenderedCarve`; `text()`, `markdown()`, and `toc()` expose other outputs. The helper's `debug` option appends conversion time in an HTML comment.

Use `CarveView::class` as a controller's view class to render `.crv` templates. Simple `{{variable}}` placeholders are replaced from view variables before conversion. Carve layouts insert the rendered body with `{{content}}`. They can use nested metadata placeholders such as `{{carve.meta.title}}`; layouts also receive `$carve['meta']` and `$carve['toc']` as view variables. Escape or sanitize untrusted values before using them in placeholders.
