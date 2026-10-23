<?php

declare(strict_types=1);

namespace MarkupCarve\CakeCarve\Carve;

use MarkupCarve\Carve\Converter\MigrationDiagnostic;
use MarkupCarve\Carve\Converter\MigrationResult;

final class ImportResult
{
    /**
     * @param string $value
     * @param string $sourceFormat
     * @param array<\MarkupCarve\Carve\Converter\MigrationDiagnostic> $diagnostics
     * @param \MarkupCarve\Carve\Converter\MigrationResult $engineResult
     */
    public function __construct(
        public readonly string $value,
        public readonly string $sourceFormat,
        public readonly array $diagnostics,
        public readonly MigrationResult $engineResult,
    ) {
    }

    /**
     * @return array<\MarkupCarve\Carve\Converter\MigrationDiagnostic>
     */
    public function losses(): array
    {
        return array_values(array_filter(
            $this->diagnostics,
            static fn (MigrationDiagnostic $item): bool => in_array($item->fidelity, ['degraded', 'dropped'], true),
        ));
    }
}
