# Safe mode

Render visitor comments and other untrusted text with the `comment` preset. It selects strict safe mode and the comment profile.

## Render a comment

```php
$renderer = new \MarkupCarve\CakeCarve\Carve\CarveRenderer();
echo $renderer->toHtml($comment->body, ['preset' => 'comment']);
```

For this Carve input:

````carve
Hello, *CakePHP*.

```=html
<script>alert(1)</script>
```
````

The result is:

```html
<p>Hello, <strong>CakePHP</strong>.</p>
```

Strict safe mode drops the raw HTML block. The default safe mode does not run it either, but shows it as escaped code:

```html
<p>Hello, <strong>CakePHP</strong>.</p>
<pre><code class="language-html">&lt;script&gt;alert(1)&lt;/script&gt;
</code></pre>
```

[Validate](validation.md) comments before storing them, and save the source with the [behavior](behavior.md) if you need generated HTML.

## Choose a trust level

| Setting | Use |
| --- | --- |
| `preset => 'comment'` | Visitor content; strict safe mode and the comment profile. |
| `safeMode => true` | General safe rendering with the engine's safe mode. This is the default. |
| `safeMode => 'strict'` | Stricter engine policy for untrusted content. |
| `preset => 'trusted'` | Author controlled content only; turns safe mode off. |

Under `safeMode => true`, `rawHtml` can be `strip`, `escape`, or `allow`; its default is `null`, which keeps the engine setting. `allow` passes explicit raw HTML through, so reserve it for trusted authors. `rawHtml` does not override strict safe mode. The `profile` option accepts `full`, `article`, `comment`, or `minimal`. `onDisallowed` accepts `strip` (default), `to_text`, or `error`. Use `error` when an editor should correct rejected markup instead of having it removed.

Bare HTML and unsafe link schemes have their own engine handling. See the [Carve security guide](https://markup-carve.github.io/carve/security) for those rules.
