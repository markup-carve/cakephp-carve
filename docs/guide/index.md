# Getting started

[Carve](https://markup-carve.github.io/carve/) is a text markup language for headings, links, lists, and other document content. This CakePHP plugin renders Carve text in templates and provides validation, stored HTML, commands, and caching.

## Render your first text in five minutes

1. [Install the plugin](installation.md) with Composer and load it in `Application::bootstrap()`.
2. Add the `MarkupCarve/CakeCarve.Carve` helper in `AppView::initialize()`.
3. Put this in a CakePHP template:

```php
<?= $this->Carve->convert('Hello, *CakePHP*.') ?>
```

The page receives:

```html
<p>Hello, <strong>CakePHP</strong>.</p>
```

4. For saved content, attach the [behavior](behavior.md) to generate an HTML column.
5. Add [validation](validation.md) for length and allowed markup. Render visitor text with the [comment preset](safe-mode.md).

## Find the next task

| Task | Guide |
| --- | --- |
| Learn the markup | [Carve syntax](carve-syntax.md) |
| Render text or files | [Rendering and options](rendering-and-options.md) |
| Render templates | [Views and helper](views-and-helper.md) |
| Change app defaults | [Configuration](configuration.md) |
| Process files from the shell | [Commands](commands.md) |
| Reuse render results | [Caching](caching.md) |
| Add markup features | [Extensions](extensions.md) |
