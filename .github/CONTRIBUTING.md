# Contributing

Use the [issue tracker](https://github.com/markup-carve/cakephp-carve/issues) for bugs and feature requests. It is not a support forum. Include a small reproduction for bug reports.

Branch from `main` and submit a pull request with tests for behavior changes. Run `composer install` to set up the project, then check your work with:

```sh
composer test
composer stan
composer cs-check
```

Run `composer cs-fix` to apply coding style fixes. The project uses `php-collective/code-sniffer`.

Documentation lives in `docs/guide/`. To build the site, run `npm install` and `npm run docs:build` from `docs/`.
