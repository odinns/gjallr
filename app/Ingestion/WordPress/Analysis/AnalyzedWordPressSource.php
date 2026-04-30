<?php

declare(strict_types=1);

namespace App\Ingestion\WordPress\Analysis;

use Carbon\CarbonImmutable;

final readonly class AnalyzedWordPressSource
{
    /**
     * @param  array<int, string>  $tables
     * @param  array<int, string>  $wordpressTables
     * @param  array<int, array{slug: string, name: string|null, version: string|null}>  $plugins
     * @param  array<int, array{slug: string, name: string|null, version: string|null}>  $themes
     * @param  array<string, bool>  $capabilities
     * @param  array<int, string>  $suspiciousFindings
     * @param  array<int, string>  $seoPluginSlugs
     */
    public function __construct(
        public string $sourceLabel,
        public CarbonImmutable $analyzedAt,
        public ?string $sqlDumpPath,
        public ?string $sitePath,
        public ?string $detectedPrefix,
        public ?string $detectedVersion,
        public ?string $detectedDbVersion,
        public string $compatibilityBand,
        public ?string $siteUrl,
        public ?string $homeUrl,
        public ?string $permalinkStructure,
        public ?string $activeTheme,
        public bool $hasUploads,
        public array $tables,
        public array $wordpressTables,
        public array $plugins,
        public array $themes,
        public array $capabilities,
        public array $suspiciousFindings,
        public array $seoPluginSlugs,
    ) {}

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'source_label' => $this->sourceLabel,
            'analyzed_at' => $this->analyzedAt->toIso8601String(),
            'sql_dump_path' => $this->sqlDumpPath,
            'site_path' => $this->sitePath,
            'detected_prefix' => $this->detectedPrefix,
            'detected_version' => $this->detectedVersion,
            'detected_db_version' => $this->detectedDbVersion,
            'compatibility_band' => $this->compatibilityBand,
            'site_url' => $this->siteUrl,
            'home_url' => $this->homeUrl,
            'permalink_structure' => $this->permalinkStructure,
            'active_theme' => $this->activeTheme,
            'has_uploads' => $this->hasUploads,
            'tables' => $this->tables,
            'wordpress_tables' => $this->wordpressTables,
            'plugins' => $this->plugins,
            'themes' => $this->themes,
            'capabilities' => $this->capabilities,
            'seo_plugin_slugs' => $this->seoPluginSlugs,
            'suspicious_findings' => $this->suspiciousFindings,
        ];
    }
}
