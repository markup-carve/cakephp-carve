# Behavior

Attach `MarkupCarve/CakeCarve.Carve` to a table. `fields` maps source fields to HTML targets. A string target is sufficient, or use an array with `target`, `preset`, `options`, `toc`, and `text`. The behavior updates generated fields when the source is dirty, the HTML target is empty, or the HTML target itself was changed: a value set directly on the target is overwritten from the source, so the stored HTML always comes from the renderer. Clearing the source also clears the generated fields.

```php
$this->addBehavior('MarkupCarve/CakeCarve.Carve', [
    'fields' => ['body' => ['target' => 'body_html', 'preset' => 'comment', 'text' => 'body_text']],
]);
```
