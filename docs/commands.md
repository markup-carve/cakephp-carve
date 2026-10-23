# Commands

```sh
bin/cake carve render article.crv --preset comment --output article.html
bin/cake carve convert --from markdown article.md --output article.crv --check-loss
bin/cake carve format --check article.crv
bin/cake carve format --write article.crv
bin/cake carve lint article.crv --include-root /absolute/content/root
```

`format` and `lint` also accept directories and process their `.crv` files recursively. Each command exits with a nonzero status for unreadable files or processing errors. `format --check` fails when a file needs formatting. `lint` fails when it finds issues. `convert --check-loss` fails when migration diagnostics report degraded or dropped content.

`render --format` accepts `html` (the default), `text`, `markdown`, or `ansi`. For example, run `bin/cake carve render article.crv --format ansi`. `format --align-tables` passes table alignment to the engine formatter, including with `--check` and `--write`.
