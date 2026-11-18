<?php

declare(strict_types=1);

namespace MarkupCarve\CakeCarve\Carve;

use MarkupCarve\Carve2\Diagnostics\Diagnostic;
use MarkupCarve\Carve2\Import\ImportResult as EngineImportResult;

final class ImportResult
{
    /**
     * @param string $value
     * @param string $sourceFormat
     * @param array<\MarkupCarve\Carve2\Diagnostics\Diagnostic> $diagnostics
     * @param \MarkupCarve\Carve2\Import\ImportResult $engineResult
     */
    public function __construct(
        public readonly string $value,
        public readonly string $sourceFormat,
        public readonly array $diagnostics,
        public readonly EngineImportResult $engineResult,
    ) {
    }

    /**
     * @return array<\MarkupCarve\Carve2\Diagnostics\Diagnostic>
     */
    public function losses(): array
    {
        return array_values(array_filter(
            $this->diagnostics,
            static fn (Diagnostic $item): bool => in_array($item->channel, ['import-loss', 'render-loss'], true),
        ));
    }
}
