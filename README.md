# CakePHP Carve 0.2

This `next2` branch ports the CakePHP plugin to the unreleased Carve PHP engine. It pins the engine's `next2` commit through Composer's VCS support. The branch is for integration testing and is not a package release.

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

`CarveView`, the view helper, validation rule, ORM behavior, and the `carve render`, `format`, `lint`, and `convert` commands use the same renderer. HTML is escaped by default, and unsafe URL schemes are denied. HTML import uses Carve; Markdown, Djot, and BBCode importers have been removed. File rendering accepts `includeRoot` or a Carve include resolver.

Text, Markdown, and ANSI output methods remain in the plugin. They use the Carve writer registry and report a clear error until a writer is registered. Several 0.1 extensions also lack a 0.2 definition. See [rendering and options](docs/guide/rendering-and-options.md), [extensions](docs/guide/extensions.md), [commands](docs/guide/commands.md), and [configuration](config/app.example.php) for details.
