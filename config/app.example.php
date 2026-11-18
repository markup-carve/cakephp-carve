<?php

declare(strict_types=1);

return [
    'Carve' => [
        'rendererClass' => \MarkupCarve\CakeCarve\Carve\CarveRenderer::class,
        'rawHtml' => false, // Explicit raw HTML stays escaped unless this is true.
        'urlPolicy' => ['deny' => ['javascript', 'vbscript', 'data', 'file']],
        'profile' => 'full', // full|article|comment|minimal
        'mode' => 'interactive', // interactive|static
        'sections' => true,
        'smartTypography' => true,
        'sourceLines' => false,
        'strictLosses' => false,
        'roundtrip' => false,
        'symbols' => [],
        'labels' => [],
        'includeRoot' => null, // Absolute root for renderFile() and lintFile().
        'includeResolver' => null, // Carve2 IncludeResolver or callable.
        'extensions' => [], // Carve2 ExtensionDefinition or ExtensionProvider.
        'writers' => [], // Carve2 OutputWriter instances for text, markdown and ansi.
        'presets' => [
            'default' => [],
            'comment' => ['profile' => 'comment'],
            'trusted' => ['rawHtml' => true],
        ],
        'cache' => false,
        'debug' => null,
    ],
];
