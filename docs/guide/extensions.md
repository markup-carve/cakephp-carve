# Extensions

Set `extensions` to engine extension names, option arrays, class names, or instances. Names and option aliases follow the Laravel sibling package's extension factory. For example:

```php
'extensions' => [
    'frontmatter',
    ['type' => 'heading_permalinks', 'class' => 'heading-link'],
    ['type' => 'wikilinks', 'url_template' => '/wiki/{page}'],
],
```

The table of contents extension is added automatically unless configured explicitly. The `toc()` result contains a flat list of heading level, text, HTML, and anchor ID.
