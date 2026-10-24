# Carve syntax

Carve looks familiar to Markdown readers, but its emphasis marks differ. Start with the sample below; use the [full Carve docs](https://markup-carve.github.io/carve/) for the complete language.

## Write a short document

````carve
# Notes

/italic/, *bold*, _underline_, ~strike~, =highlight=, H{,2,}O and x{^2^}.

[Carve](https://markup-carve.github.io/carve/)

- First item
- Second item

```php
echo 'hello';
```

> A short quote.

|= Name |= Role |
| Ada | Editor |
````

A heading starts with `#`. Links use `[label](url)`. A list needs a blank line before its first item. Code fences put the language directly after the backticks, with no space. `|=` marks table headers.

## Check the marks

| Write | HTML element |
| --- | --- |
| `/italic/` | `<em>` |
| `*bold*` | `<strong>` |
| `_underline_` | `<u>` |
| `~strike~` | `<s>` |
| `=highlight=` | `<mark>` |
| `{^sup^}` | `<sup>` |
| `{,sub,}` | `<sub>` |

The sample's first paragraph renders as:

```html
<p><em>italic</em>, <strong>bold</strong>, <u>underline</u>, <s>strike</s>, <mark>highlight</mark>, H<sub>2</sub>O and x<sup>2</sup>.</p>
```
