# Validation

Register `CarveValidation::class` as a CakePHP validator provider and add the `valid` rule. Empty values pass; use CakePHP's presence and nonempty rules when required. The rule accepts `preset`, `profile`, `lint`, `lintRules`, `ignoreLintRules`, `minLength`, and `maxLength`. Length is measured in rendered plain text. A disallowed profile construct fails. Set `lint` to `true` to fail on lint findings; the message names up to three of them as `line:column rule: message`. Use `lintRules` to allow only named rule IDs or `ignoreLintRules` to exclude named rule IDs. Findings from carve-php 0.1.14 have no severity.

```php
$validator->setProvider('carve', \MarkupCarve\CakeCarve\Validation\CarveValidation::class)
    ->add('body', 'carve', ['rule' => ['valid', ['profile' => 'comment']], 'provider' => 'carve']);
```

Set `strict` to `true` to reject any engine parse warning. The error names up to three warnings with line, column, rule, and message.
