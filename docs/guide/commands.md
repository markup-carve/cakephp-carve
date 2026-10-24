# Commands

Use Cake's `carve` commands to process files from the shell.

## Render a file

```sh
bin/cake carve render article.crv --preset comment --output article.html
bin/cake carve render article.crv --format ansi
```

`render` writes HTML by default. `--format` also accepts `text`, `markdown`, and `ansi`.

## Convert existing markup

```sh
bin/cake carve convert --from markdown article.md --output article.crv --check-loss
```

`--from` accepts `markdown`, `html`, `djot`, or `bbcode`. `--check-loss` fails when conversion reports degraded or dropped content; review the result before replacing your original.

## Format Carve files

```sh
bin/cake carve format --check article.crv
bin/cake carve format --write article.crv
```

`--check` fails if formatting would change the file. `--write` applies it. Add `--align-tables` when you want padded table cells. This command also accepts directories and processes `.crv` files recursively.

## Lint Carve files

```sh
bin/cake carve lint article.crv --include-root /absolute/content/root
```

`lint` reports file, line, column, rule, and message. It accepts directories and exits with a nonzero status for findings or processing errors.

Output writes use a temporary file in the target directory and replace the target after the write completes. The replacement keeps mode, owner, and group, but it does not keep POSIX ACLs, extended attributes, or old hard links. The target directory must be writable.
