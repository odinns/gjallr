<?php

declare(strict_types=1);

namespace App\Ingestion\WordPress\Analysis;

use Carbon\CarbonImmutable;
use Illuminate\Support\Str;

final class WordPressSourceAnalyzer
{
    public function __construct(
        private readonly SqlDumpScanner $sqlDumpScanner,
        private readonly SiteTreeScanner $siteTreeScanner,
    ) {}

    public function analyze(string $sourceLabel, ?string $sqlDumpPath = null, ?string $sitePath = null): AnalyzedWordPressSource
    {
        $sqlDump = $sqlDumpPath !== null ? $this->sqlDumpScanner->scan($sqlDumpPath) : new SqlDumpAnalysis(
            tables: [],
            wordpressTables: [],
            detectedPrefix: null,
            optionValues: [],
            suspiciousFindings: [],
            capabilities: [
                'comments' => false,
                'menus' => false,
                'seo_meta' => false,
                'terms' => false,
            ],
        );
        $siteTree = $sitePath !== null ? $this->siteTreeScanner->scan($sitePath) : new SiteTreeAnalysis(
            coreVersion: null,
            coreDbVersion: null,
            hasUploads: false,
            plugins: [],
            themes: [],
            seoPluginSlugs: [],
        );

        $detectedVersion = $siteTree->coreVersion;
        $detectedDbVersion = $siteTree->coreDbVersion ?? $sqlDump->optionValues['db_version'] ?? null;
        $compatibilityBand = $this->detectCompatibilityBand($detectedVersion);
        $activeTheme = $sqlDump->optionValues['stylesheet'] ?? $sqlDump->optionValues['template'] ?? null;
        $seoPluginSlugs = array_values(array_unique(array_merge(
            $siteTree->seoPluginSlugs,
            $this->extractSeoPluginSlugs($sqlDump->optionValues['active_plugins'] ?? ''),
        )));

        $capabilities = array_merge($sqlDump->capabilities, [
            'uploads' => $siteTree->hasUploads,
            'themes' => $siteTree->themes !== [],
            'plugins' => $siteTree->plugins !== [],
            'seo_meta' => $sqlDump->capabilities['seo_meta'] || $seoPluginSlugs !== [],
        ]);

        return new AnalyzedWordPressSource(
            sourceLabel: $sourceLabel,
            analyzedAt: CarbonImmutable::now(),
            sqlDumpPath: $sqlDumpPath,
            sitePath: $sitePath,
            detectedPrefix: $sqlDump->detectedPrefix,
            detectedVersion: $detectedVersion,
            detectedDbVersion: $detectedDbVersion,
            compatibilityBand: $compatibilityBand,
            siteUrl: $sqlDump->optionValues['siteurl'] ?? null,
            homeUrl: $sqlDump->optionValues['home'] ?? null,
            permalinkStructure: $sqlDump->optionValues['permalink_structure'] ?? null,
            activeTheme: $activeTheme,
            hasUploads: $siteTree->hasUploads,
            tables: $sqlDump->tables,
            wordpressTables: $sqlDump->wordpressTables,
            plugins: $siteTree->plugins,
            themes: $siteTree->themes,
            capabilities: $capabilities,
            suspiciousFindings: $sqlDump->suspiciousFindings,
            seoPluginSlugs: $seoPluginSlugs,
        );
    }

    private function detectCompatibilityBand(?string $version): string
    {
        if ($version === null || ! preg_match('/^(?<major>\d+)\./', $version, $matches)) {
            return (string) config('gjallr.analysis.version_fallback_band');
        }

        $major = (int) $matches['major'];

        if ($major >= 4 && $major <= 6) {
            return (string) config('gjallr.analysis.compatibility_band');
        }

        return $major.'.x';
    }

    /**
     * @return array<int, string>
     */
    private function extractSeoPluginSlugs(string $activePlugins): array
    {
        if ($activePlugins === '') {
            return [];
        }

        $matches = [];
        preg_match_all('/s:\d+:"([^"]+)"/', $activePlugins, $matches);
        $pluginPaths = $matches[1];
        $candidates = [];

        foreach ($pluginPaths as $pluginPath) {
            $slug = Str::before($pluginPath, '/');

            if (in_array($slug, (array) config('gjallr.analysis.seo_plugin_slugs', []), true)) {
                $candidates[] = $slug;
            }
        }

        return array_values(array_unique($candidates));
    }
}
