<?php

declare(strict_types=1);

namespace App\Ingestion\WordPress\Analysis;

final readonly class SqlDumpAnalysis
{
    /**
     * @param  array<int, string>  $tables
     * @param  array<int, string>  $wordpressTables
     * @param  array<string, string>  $optionValues
     * @param  array<int, string>  $suspiciousFindings
     * @param  array<string, bool>  $capabilities
     */
    public function __construct(
        public array $tables,
        public array $wordpressTables,
        public ?string $detectedPrefix,
        public array $optionValues,
        public array $suspiciousFindings,
        public array $capabilities,
    ) {}
}
