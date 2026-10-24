# Validate Carve input

Add the validator rule to reject content that exceeds a length limit or uses markup your application does not allow.

## Validate a visitor comment

In a table's `validationDefault()` method:

```php
$validator->setProvider('carve', \MarkupCarve\CakeCarve\Validation\CarveValidation::class)
    ->add('body', 'carve', [
        'rule' => ['valid', ['preset' => 'comment', 'profile' => 'comment', 'maxLength' => 500]],
        'provider' => 'carve',
    ]);
```

For a five-character limit, `CarveValidation::valid('Hello world', ['preset' => 'comment', 'maxLength' => 5])` returns `Carve content is too long.` Length is measured in rendered plain text. Empty values pass this rule; add CakePHP presence and nonempty rules when content is required.

## Choose rule options

| Option | Default | What it does |
| --- | --- | --- |
| `preset` | `default` | Uses a configured render preset. |
| `profile` | From preset or config | Limits markup; `comment` suits visitor text. |
| `onDisallowed` | `strip` | Selects `strip`, `to_text`, or `error` for disallowed markup. |
| `strict` | `false` | Rejects parse warnings. |
| `lint` | `false` | Rejects lint findings. |
| `lintRules` | All rules | Limits lint checks to listed rule IDs. |
| `ignoreLintRules` | `[]` | Excludes listed lint rule IDs. |
| `minLength` | None | Requires a minimum plain text length. |
| `maxLength` | None | Sets a maximum plain text length. |

Profile violations return `Carve content contains markup disallowed by its profile.` Lint and parse messages show up to three findings with line, column, rule, and message. The engine's lint findings have no severity field. See [safe mode](safe-mode.md) for render settings to pair with validation.
