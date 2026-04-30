<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Ingestion\WordPress\Analysis\WordPressSourceAnalyzer;
use App\Ingestion\WordPress\Import\SqlDumpLoader;
use App\Ingestion\WordPress\Import\WordPressImporter;
use App\Models\ImportRun;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;
use Throwable;

#[Signature('gjallr:import
    {--sql-dump= : Path to a plain .sql or .sql.gz dump}
    {--site-path= : Path to a WordPress site tree}
    {--source-label=sample : Human-readable label for this source}
    {--load-dump : Load the SQL dump into the current mysql database before importing}
    {--dry-run : Analyze only and skip the runtime import}')]
#[Description('Import a WordPress source into Gjallr runtime tables')]
class ImportWordPressSourceCommand extends Command
{
    public function __construct(
        private readonly WordPressSourceAnalyzer $analyzer,
        private readonly SqlDumpLoader $sqlDumpLoader,
        private readonly WordPressImporter $importer,
    ) {
        parent::__construct();
    }

    public function handle(): int
    {
        $sqlDumpPath = (string) ($this->option('sql-dump') ?: config('gjallr.samples.default.sql_dump'));
        $sitePath = (string) ($this->option('site-path') ?: config('gjallr.samples.default.site_path'));
        $sourceLabel = (string) $this->option('source-label');

        $analysis = $this->analyzer->analyze(
            sourceLabel: $sourceLabel,
            sqlDumpPath: $sqlDumpPath !== '' ? $sqlDumpPath : null,
            sitePath: $sitePath !== '' ? $sitePath : null,
        );

        $importRun = ImportRun::query()->create([
            'source_label' => $sourceLabel,
            'status' => 'running',
            'sql_dump_path' => $analysis->sqlDumpPath,
            'site_path' => $analysis->sitePath,
            'detected_prefix' => $analysis->detectedPrefix,
        ]);

        try {
            if ((bool) $this->option('load-dump') && $analysis->sqlDumpPath !== null) {
                $this->components->info('Loading SQL dump into the current database...');
                $this->sqlDumpLoader->loadIntoCurrentDatabase($analysis->sqlDumpPath, $analysis->detectedPrefix);
            }

            if ((bool) $this->option('dry-run')) {
                $importRun->forceFill([
                    'status' => 'dry-run',
                    'summary_json' => ['analysis_only' => true],
                ])->save();

                $this->components->info('Dry run complete.');

                return self::SUCCESS;
            }

            $site = $this->importer->import($importRun, $analysis);

            $this->table(
                ['Field', 'Value'],
                [
                    ['Site', $site->name ?? $sourceLabel],
                    ['Source label', $sourceLabel],
                    ['Prefix', $analysis->detectedPrefix ?? 'unknown'],
                    ['Imported content items', (string) $site->contentItems()->count()],
                    ['Imported menus', (string) $site->navigationMenus()->count()],
                ],
            );

            return self::SUCCESS;
        } catch (Throwable $throwable) {
            $importRun->forceFill([
                'status' => 'failed',
                'notes' => $throwable->getMessage(),
            ])->save();

            $this->error($throwable->getMessage());

            return self::FAILURE;
        }
    }
}
