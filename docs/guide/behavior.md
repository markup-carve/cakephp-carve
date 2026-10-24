# Store rendered content

The Carve behavior generates HTML, optional plain text, and optional TOC fields when a CakePHP entity is saved.

## Add columns

For a `posts` table, keep the source and generated HTML in separate columns. Add `body_text` when you need search or previews:

```php
$this->table('posts')
    ->addColumn('body', 'text', ['null' => true])
    ->addColumn('body_html', 'text', ['null' => true])
    ->addColumn('body_text', 'text', ['null' => true])
    ->update();
```

This is the body of a migration's `change()` method. Include the usual migration class and table setup for your app.

## Attach the behavior

In `PostsTable::initialize()`:

```php
$this->addBehavior('MarkupCarve/CakeCarve.Carve', [
    'fields' => [
        'body' => [
            'target' => 'body_html',
            'text' => 'body_text',
            'preset' => 'comment',
        ],
    ],
]);
```

If `body` is `Hello, *CakePHP*.`, saving sets `body_html` to `<p>Hello, <strong>CakePHP</strong>.</p>`. The behavior rerenders when the source changes, the HTML is empty, or a generated field was changed directly. It overwrites a direct edit to a generated field from the source. Clearing the source clears HTML and text; an optional TOC field becomes an empty array. If the source was not loaded, a directly changed generated field is unset instead of saved.

## Configure a field

| Field key | Value | What it does |
| --- | --- | --- |
| `target` | Column name | Stores rendered HTML; defaults to `<source>_html` for an array spec. |
| `preset` | Preset name | Selects render options for this field. |
| `options` | Option map | Passes options to the renderer. |
| `text` | Column name | Stores plain text. |
| `toc` | Column name | Stores heading records; choose a column type that can hold an array. |

A short form is `'fields' => ['body' => 'body_html']`. Validate visitor input with the [comment profile](validation.md) and render it with the [comment preset](safe-mode.md).
