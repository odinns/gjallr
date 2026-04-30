<?php

declare(strict_types=1);

namespace App\Ingestion\WordPress\Analysis;

final readonly class SiteTreeAnalysis
{
    /**
     * @param  array<int, array{slug: string, name: string|null, version: string|null}>  $plugins
     * @param  array<int, array{slug: string, name: string|null, version: string|null}>  $themes
     * @param  array<int, string>  $seoPluginSlugs
     */
    public function __construct(
        public ?string $coreVersion,
        public ?string $coreDbVersion,
        public bool $hasUploads,
        public array $plugins,
        public array $themes,
        public array $seoPluginSlugs,
    ) {}
}
