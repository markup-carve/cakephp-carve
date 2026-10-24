# Views and helper

Use the helper in ordinary CakePHP templates. Use `CarveView` when the template itself is a `.crv` file.

## Render text in a PHP template

Add the helper in `AppView::initialize()` as shown in [installation](installation.md). In a template:

```php
<?= $this->Carve->convert('Hello, *CakePHP*.') ?>
```

```html
<p>Hello, <strong>CakePHP</strong>.</p>
```

The helper trims output. With `debug` enabled, `convert()` appends a timing HTML comment.

## Use the other helper methods

| Method | Example | Result |
| --- | --- | --- |
| `render` | `$this->Carve->render('# Hello')` | `RenderedCarve` with HTML, TOC, and metadata. |
| `file` | `$this->Carve->file(ROOT . '/content/page.crv')` | HTML from a file. |
| `text` | `$this->Carve->text('Hello, *CakePHP*.')` | Plain text. |
| `markdown` | `$this->Carve->markdown('Hello, *CakePHP*.')` | Markdown text. |
| `toc` | `$this->Carve->toc('# Hello')` | Heading records. |
| `lint` | `$this->Carve->lint('Hello')` | Lint findings. |
| `format` | `$this->Carve->format("Hello\n")` | Formatted Carve source. |

## Use a `.crv` template

Select the plugin view class in a controller action:

```php
use MarkupCarve\CakeCarve\View\CarveView;

$this->viewBuilder()->setClassName(CarveView::class);
$this->set('name', 'Ada');
```

Create `templates/Pages/welcome.crv`:

```carve
# Hello

Welcome, {{name}}.
```

The view replaces the placeholder before rendering. With `name` set to `Ada`, the HTML is:

```html
<section id="Hello">
  <h1>Hello</h1>
  <p>Welcome, Ada.</p>
</section>
```

## Add a Carve layout

::: v-pre

Create a `.crv` layout, such as `templates/layout/default.crv`:

```carve
# {{carve.meta.title}}

{{content}}
```

`{{content}}` inserts the rendered body. Frontmatter metadata is available through `{{carve.meta.title}}`; the view also sets `$carve['meta']` and `$carve['toc']`. Put placeholders only in trusted templates, and escape or sanitize untrusted values before assigning view variables used in them.

:::
