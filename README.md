# CakePHP Carve

[![CI](https://github.com/markup-carve/cakephp-carve/actions/workflows/ci.yml/badge.svg?branch=main)](https://github.com/markup-carve/cakephp-carve/actions/workflows/ci.yml?query=branch%3Amain)
[![Coverage](https://img.shields.io/codecov/c/github/markup-carve/cakephp-carve/main.svg)](https://app.codecov.io/github/markup-carve/cakephp-carve/tree/main)
[![PHPStan level](https://img.shields.io/badge/PHPStan-level%209-brightgreen.svg)](https://phpstan.org/)
[![Latest Stable Version](https://poser.pugx.org/markup-carve/cakephp-carve/v/stable.svg)](https://packagist.org/packages/markup-carve/cakephp-carve)
[![Minimum PHP Version](https://img.shields.io/badge/php-%3E%3D%208.2-8892BF.svg)](https://php.net/)
[![License](https://poser.pugx.org/markup-carve/cakephp-carve/license.svg)](LICENSE)
[![Total Downloads](https://poser.pugx.org/markup-carve/cakephp-carve/d/total.svg)](https://packagist.org/packages/markup-carve/cakephp-carve)

CakePHP wrapper for [Carve](https://github.com/markup-carve/carve-php) rendering, templates, validation, persistence, and commands.

**[View the live demo →](https://sandbox.dereuromark.de/sandbox/cake-carve-examples)**: presets and safe mode, validation, the ORM behavior and `.crv` views in a running CakePHP app.

See [composer.json](composer.json) for supported versions and the [version map](https://github.com/markup-carve/cakephp-carve/wiki#cakephp-version-map) for which plugin line matches which CakePHP and carve-php release.

## Install

```sh
composer require markup-carve/cakephp-carve
```

Load the plugin in your application:

```php
$this->addPlugin('MarkupCarve/CakeCarve');
```

Load the helper in `AppView::initialize()`:

```php
$this->addHelper('MarkupCarve/CakeCarve.Carve');
```

## Use

```php
$carve = new \MarkupCarve\CakeCarve\Carve\CarveRenderer();
$html = $carve->toHtml('# Hello');
$result = $carve->render('# Hello', ['preset' => 'comment']);
$toc = $result->toc();
$text = $carve->toText('Hello');
$markdown = $carve->toMarkdown('Hello');
$ansi = $carve->toAnsi('Hello');
$file = $carve->renderFile('/path/to/article.crv');
$formatted = $carve->format('Hello');
$findings = $carve->lint('Hello');
$imported = $carve->import('**Hello**', 'markdown');
```

In a template, call the helper's `convert`, `render`, `text`, `markdown`, `toc`, `file`, `lint`, or `format` methods. For `.crv` templates, use `CarveView::class` as the controller view class. The view supplies `$carve['meta']` and `$carve['toc']` to layouts.

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

See [the documentation](https://markup-carve.github.io/cakephp-carve/) and [configuration example](config/app.example.php).
