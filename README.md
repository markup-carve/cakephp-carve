# CakePHP Carve

CakePHP 5 support for [Carve](https://github.com/markup-carve/carve-php) rendering, templates, validation, persistence, and commands.

See [composer.json](composer.json) for supported versions and the [version map](https://github.com/markup-carve/cakephp-carve/wiki#cakephp-version-map) for which plugin line matches which CakePHP and carve-php release.

## Install

```sh
composer require markup-carve/cakephp-carve
```

Load the plugin in your application:

```php
$this->addPlugin('MarkupCarve/CakeCarve');
```

## Use

```php
$carve = new \MarkupCarve\CakeCarve\Carve\CarveRenderer();
$html = $carve->toHtml('# Hello');
$result = $carve->render('# Hello', ['preset' => 'comment']);
$toc = $result->toc();
$text = $carve->toText('Hello');
$markdown = $carve->toMarkdown('Hello');
$formatted = $carve->format('Hello');
$findings = $carve->lint('Hello');
$imported = $carve->import('**Hello**', 'markdown');
```

In a template, load the helper and call `$this->Carve->convert($source)`, `render()`, `text()`, `markdown()`, or `toc()`. For `.crv` templates, use `CarveView::class` as the controller view class. The view supplies `$carve['meta']` and `$carve['toc']` to layouts.

Add `CarveValidation::class` as a validator provider:

```php
$validator->setProvider('carve', \MarkupCarve\CakeCarve\Validation\CarveValidation::class)
    ->add('body', 'carve', ['rule' => ['valid', ['preset' => 'comment']], 'provider' => 'carve']);
```

Attach `CarveBehavior` to render source fields when an entity is saved:

```php
$this->addBehavior('MarkupCarve/CakeCarve.Carve', [
    'fields' => ['body' => ['target' => 'body_html', 'text' => 'body_text']],
]);
```

Run `bin/cake carve render`, `carve convert`, `carve format`, or `carve lint` for file operations.

See [the documentation](docs/README.md) and [configuration example](config/app.example.php).
