# Commands in Carve 0.2

```sh
bin/cake carve render article.crv --output article.html
bin/cake carve format --check article.crv
bin/cake carve format --write article.crv
bin/cake carve lint article.crv
bin/cake carve convert --from html article.html --output article.crv --check-loss
```

`render` writes HTML by default. `--format text`, `markdown`, and `ansi` remain accepted and use the 0.2 writer registry. Until a writer is registered, the command reports that the writer is not available yet. `format` writes canonical 0.2 source and accepts `--align-tables`. `lint` reports the 0.2 rule code and source position. `convert` imports HTML through Carve2. Markdown, Djot, and BBCode imports use the retained 0.1 importers and format their output for 0.2. They report `legacy-import-review`, so `--check-loss` refuses them. Run without that flag, then review the output with the 0.2 upgrade tool. `--check-loss` refuses an HTML import with an import-loss diagnostic before writing the output file.
