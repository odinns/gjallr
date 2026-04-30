<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Ingestion\Wayback\WaybackAvailabilityAnalyzer;
use App\Ingestion\WordPress\Analysis\WordPressSourceAnalyzer;
use App\Models\SourceAnalysisRun;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;

#[Signature('gjallr:analyze-source
    {--sql-dump= : Path to a plain .sql or .sql.gz dump}
    {--site-path= : Path to a WordPress site tree}
    {--source-label=sample : Human-readable label for this source}
    {--with-wayback : Include a bounded Wayback availability check}
    {--json : Print the full JSON profile to stdout}')]
#[Description('Analyze a WordPress source and write a compatibility profile artifact')]
class AnalyzeWordPressSourceCommand extends Command
{
    public function __construct(
        private readonly WordPressSourceAnalyzer $analyzer,
        private readonly WaybackAvailabilityAnalyzer $wayback,
    ) {
        parent::__construct();
    }

    public function handle(): int
    {
        $sqlDumpPath = $this->resolveOptionPath('sql-dump', (string) config('gjallr.samples.default.sql_dump'));
        $sitePath = $this->resolveOptionPath('site-path', (string) config('gjallr.samples.default.site_path'));
        $sourceLabel = (string) $this->option('source-label');

        if ($sqlDumpPath === null && $sitePath === null) {
            $this->error('Nothing to analyze. Provide --sql-dump, --site-path, or configure defaults in config/gjallr.php.');

            return self::FAILURE;
        }

        foreach (['SQL dump' => $sqlDumpPath, 'site path' => $sitePath] as $label => $path) {
            if ($path !== null && ! File::exists($path)) {
                $this->error($label.' does not exist: '.$path);

                return self::FAILURE;
            }
        }

        $analysis = $this->analyzer->analyze(
            sourceLabel: $sourceLabel,
            sqlDumpPath: $sqlDumpPath,
            sitePath: $sitePath,
        );

        $waybackSummary = (bool) $this->option('with-wayback')
            ? $this->wayback->summarize($analysis->homeUrl, $analysis->siteUrl)
            : null;
        $payload = $analysis->toArray();

        if ($waybackSummary !== null) {
            $payload['wayback'] = $waybackSummary;
        }

        $artifactPath = $this->writeArtifact($payload, $sourceLabel);

        $summaryJson = [
            'capabilities' => $analysis->capabilities,
            'active_theme' => $analysis->activeTheme,
            'permalink_structure' => $analysis->permalinkStructure,
            'seo_plugin_slugs' => $analysis->seoPluginSlugs,
        ];

        if ($waybackSummary !== null) {
            $summaryJson['wayback'] = $waybackSummary;
        }

        $run = SourceAnalysisRun::query()->create([
            'source_type' => 'wordpress',
            'source_label' => $sourceLabel,
            'sql_dump_path' => $sqlDumpPath,
            'site_path' => $sitePath,
            'detected_prefix' => $analysis->detectedPrefix,
            'detected_version' => $analysis->detectedVersion,
            'detected_db_version' => $analysis->detectedDbVersion,
            'compatibility_band' => $analysis->compatibilityBand,
            'has_uploads' => $analysis->hasUploads,
            'tables_count' => count($analysis->tables),
            'plugins_count' => count($analysis->plugins),
            'themes_count' => count($analysis->themes),
            'suspicious_findings_count' => count($analysis->suspiciousFindings),
            'artifact_path' => $artifactPath,
            'summary_json' => $summaryJson,
            'analyzed_at' => $analysis->analyzedAt,
        ]);

        $this->components->info('WordPress source profile written.');
        $this->table(
            ['Field', 'Value'],
            [
                ['Run ID', (string) $run->id],
                ['Label', $analysis->sourceLabel],
                ['Detected version', $analysis->detectedVersion ?? 'unknown'],
                ['DB version', $analysis->detectedDbVersion ?? 'unknown'],
                ['Compatibility band', $analysis->compatibilityBand],
                ['Prefix', $analysis->detectedPrefix ?? 'unknown'],
                ['Tables', (string) count($analysis->tables)],
                ['Plugins', (string) count($analysis->plugins)],
                ['Themes', (string) count($analysis->themes)],
                ['Suspicious findings', (string) count($analysis->suspiciousFindings)],
                ['Wayback captures', $waybackSummary !== null ? (string) $waybackSummary['captures_count'] : 'not checked'],
                ['Artifact', $artifactPath],
            ],
        );

        if ($analysis->suspiciousFindings !== []) {
            $this->newLine();
            $this->warn('Suspicious findings');

            foreach ($analysis->suspiciousFindings as $finding) {
                $this->line('- '.$finding);
            }
        }

        if ((bool) $this->option('json')) {
            $this->newLine();
            $this->line(json_encode($payload, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR));
        }

        return self::SUCCESS;
    }

    private function resolveOptionPath(string $option, string $default): ?string
    {
        $value = (string) ($this->option($option) ?: $default);

        if ($value === '') {
            return null;
        }

        return File::exists($value) ? realpath($value) ?: $value : $value;
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    private function writeArtifact(array $payload, string $sourceLabel): string
    {
        $timestamp = Carbon::now()->format('Ymd_His');
        $filename = $timestamp.'_'.Str::slug($sourceLabel ?: 'source').'.json';
        $relativePath = trim((string) config('gjallr.analysis.artifact_directory'), '/').'/'.$filename;
        $absolutePath = storage_path('app/'.$relativePath);

        File::ensureDirectoryExists(dirname($absolutePath));
        File::put(
            $absolutePath,
            json_encode($payload, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR)
        );

        return $relativePath;
    }
}
