# Extensions

Extensions add markup features to a renderer. Set them in the `extensions` option or under app `Carve` config.

## Add heading permalinks

```php
$renderer = new \MarkupCarve\CakeCarve\Carve\CarveRenderer();
echo $renderer->toHtml('# Hello', ['extensions' => ['heading_permalinks']]);
```

Carve input:

```carve
# Hello
```

HTML:

```html
<section id="Hello">
  <h1>Hello <a href="#Hello" class="permalink" aria-label="Permalink">¶</a></h1>
</section>
```

## Pick an extension

`ExtensionFactory` accepts the names below. It also accepts an extension class name, an instance, or an option array with a `type` key. The table of contents extension is added automatically unless you configure it explicitly.

| Name | Adds |
| --- | --- |
| `frontmatter` | Document metadata at the top of a file. |
| `heading_permalinks` | Links beside headings. |
| `table_of_contents` | Generated heading navigation. |
| `wikilinks` | Wiki style links. |
| `mentions` | Mention links. |
| `mermaid` | Mermaid fenced blocks. |
| `plantuml` | PlantUML fenced blocks. |
| `fenced_render` | A configurable fenced renderer. |
| `d2` | D2 diagram blocks. |
| `graphviz` | Graphviz diagram blocks. |
| `wavedrom` | WaveDrom diagram blocks. |
| `abc` | ABC music blocks. |
| `vega_lite` | Vega Lite visualization blocks. |
| `chart` | Chart blocks. |


The factory also accepts installed extension classes by class name or matching snake case name. For a configured wiki link target:

```php
'extensions' => [
    ['type' => 'wikilinks', 'url_template' => '/wiki/{page}'],
],
```

See [rendering options](rendering-and-options.md) for `mode` and build time `renderers`.
