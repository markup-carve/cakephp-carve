# Installation

Install the plugin in a CakePHP 5 application running PHP 8.2 or later. The package requires `cakephp/cakephp` 5.2 or later within major version 5 and `markup-carve/carve-php` 0.1.14 or later within the 0.1 series.

## Install and load the plugin

```sh
composer require markup-carve/cakephp-carve
```

In `src/Application.php`, add the plugin in `bootstrap()`:

```php
public function bootstrap(): void
{
    parent::bootstrap();
    $this->addPlugin('MarkupCarve/CakeCarve');
}
```

## Add the template helper

In `src/View/AppView.php`:

```php
public function initialize(): void
{
    parent::initialize();
    $this->addHelper('MarkupCarve/CakeCarve.Carve');
}
```

Now a template can call `<?= $this->Carve->convert('Hello, *CakePHP*.') ?>`. It emits `<p>Hello, <strong>CakePHP</strong>.</p>`.

## Set app defaults when needed

Put the `Carve` key in `config/app_local.php` or `config/app.php`:

```php
'Carve' => [
    'safeMode' => true,
    'warnings' => false,
],
```

See [configuration](configuration.md) for every key. JSON frontmatter works without another package. For YAML frontmatter metadata, add `symfony/yaml` with `composer require symfony/yaml`, or enable PHP's `yaml` extension.
