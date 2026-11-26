# CakePHP Carve 0.2

This `main` branch ports the CakePHP plugin to the unreleased Carve 0.2 PHP engine. It pins an engine commit on `main` through Composer's VCS support. The branch is for integration testing and is not a package release.

## Install for development

```sh
composer update
```

Enable the plugin and helper in your CakePHP application:

```php
$this->addPlugin('MarkupCarve/CakeCarve');
$this->addHelper('MarkupCarve/CakeCarve.Carve');
```

## Render

```php
use MarkupCarve\CakeCarve\Carve\CarveRenderer;

$carve = new CarveRenderer();
$html = $carve->toHtml('# Hello');
$result = $carve->render('# Hello', ['preset' => 'comment']);
$headings = $result->toc();
$formatted = $carve->format("a  \nb\n");
$findings = $carve->lint("{#a}\nx\n\n{#a}\ny\n");
$imported = $carve->import('<p>Hello</p>', 'html');
```

`CarveView`, the view helper, validation rule, ORM behavior, and the `carve render`, `format`, `lint`, and `convert` commands use the same renderer. HTML is escaped by default, and unsafe URL schemes are denied. HTML, Markdown and Djot import use Carve; `markdown` reads GitHub Flavored Markdown as in 0.1 (tables, task lists, strikethrough), and the BBCode importer has been removed. File rendering accepts `includeRoot` or a Carve include resolver.

Text, Markdown, and ANSI output methods use Carve's built-in writers. Named 0.2 extension bundles are configured through `extensions`. See [rendering and options](docs/guide/rendering-and-options.md), [extensions](docs/guide/extensions.md), [commands](docs/guide/commands.md), and [configuration](config/app.example.php) for details.
