<?php

declare(strict_types=1);

return [
    'Carve' => [
        'rendererClass' => \MarkupCarve\CakeCarve\Carve\CarveRenderer::class, // class-string; custom renderer class
        'safeMode' => true, // bool|'strict'; use 'strict' for untrusted comments
        'xhtml' => false, // bool; true emits self-closing tags
        'strict' => false, // bool; true throws on parse errors
        'warnings' => false, // bool; true records parse warnings
        'profile' => null, // null|'full'|'article'|'comment'|'minimal'|Profile; e.g. 'comment'
        'onDisallowed' => 'strip', // 'strip'|'to_text'|'error'; e.g. 'error'
        'mode' => 'interactive', // 'interactive'|'static'; e.g. 'static'
        'softBreakMode' => null, // null|SoftBreakMode value; e.g. 'space'
        'smartTypography' => null, // null|bool|SmartTypographyMode; e.g. true
        'symbols' => [], // array<string, string>; e.g. ['copy' => '©']
        'sourceLines' => false, // bool; e.g. true for editor previews
        'includeRoot' => null, // null|absolute path; e.g. ROOT . '/content'
        'extensions' => [], // array of names, specs, classes or instances; e.g. ['frontmatter']
        'presets' => [ // array<string, array<string, mixed>>; e.g. 'preview' => ['sourceLines' => true]
            'default' => [],
            'comment' => ['safeMode' => 'strict', 'profile' => 'comment'],
            'trusted' => ['safeMode' => false],
        ],
        'cache' => false, // false|array; e.g. ['config' => 'default', 'prefix' => 'carve_']
    ],
    // Validation rule options: lint (bool, default false), lintRules and ignoreLintRules (arrays of rule IDs).
];
