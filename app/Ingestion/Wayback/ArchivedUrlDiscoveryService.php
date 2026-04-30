<?php

declare(strict_types=1);

namespace App\Ingestion\Wayback;

use App\Models\RedirectRule;
use App\Models\RescuedSite;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;
use Odinns\LaravelWaybackMachine\CaptureScope;
use Odinns\LaravelWaybackMachine\CdxCapture;
use Odinns\LaravelWaybackMachine\CdxQuery;
use Odinns\LaravelWaybackMachine\WaybackClient;

final readonly class ArchivedUrlDiscoveryService
{
    public function __construct(
        private WaybackClient $client,
        private WaybackOptionsFactory $options,
    ) {}

    /**
     * @return array{artifact_path: string|null, suggestions: list<array<string, mixed>>}
     */
    public function discover(RescuedSite $site, int $limit, bool $dryRun): array
    {
        $baseUrl = $site->home_url ?: $site->site_url;

        if ($baseUrl === null || trim($baseUrl) === '') {
            return ['artifact_path' => null, 'suggestions' => []];
        }

        $knownPaths = $this->knownPaths($site);
        $captures = $this->client->captures(
            CaptureScope::from($baseUrl, 'host'),
            new CdxQuery(
                statuses: [200],
                mimeTypes: ['text/html'],
                limit: $limit,
                pageLimit: (int) config('gjallr.wayback.discovery_page_limit', 2),
            ),
            $this->options->make(),
        );

        $suggestions = [];

        foreach ($captures as $capture) {
            $path = $this->pathFromCapture($capture);

            if ($path === null || isset($knownPaths[$path]) || isset($suggestions[$path])) {
                continue;
            }

            $suggestions[$path] = [
                'path' => $path,
                'archived_url' => $capture->originalUrl,
                'timestamp' => $capture->timestamp,
                'status' => $capture->status,
                'mime' => $capture->mimeType,
            ];
        }

        $suggestions = array_values($suggestions);

        return [
            'artifact_path' => $dryRun ? null : $this->writeArtifact($site, $suggestions),
            'suggestions' => $suggestions,
        ];
    }

    /**
     * @return array<string, true>
     */
    private function knownPaths(RescuedSite $site): array
    {
        $paths = ['/'];

        foreach ($site->contentItems()->pluck('path') as $path) {
            $paths[] = '/'.trim((string) $path, '/');
        }

        foreach ($site->taxonomies()->pluck('path') as $path) {
            $paths[] = '/'.trim((string) $path, '/');
        }

        foreach (RedirectRule::query()->where('rescued_site_id', $site->id)->pluck('from_path') as $path) {
            $paths[] = $this->normalizePath((string) $path);
        }

        return array_fill_keys(array_map($this->normalizePath(...), $paths), true);
    }

    private function pathFromCapture(CdxCapture $capture): ?string
    {
        if ($capture->originalUrl === null) {
            return null;
        }

        $path = parse_url($capture->originalUrl, PHP_URL_PATH);

        if (! is_string($path) || $path === '') {
            return '/';
        }

        if (Str::startsWith($path, ['/wp-admin', '/wp-content', '/wp-includes'])) {
            return null;
        }

        return $this->normalizePath($path);
    }

    private function normalizePath(string $path): string
    {
        $normalized = '/'.trim($path, '/');

        return $normalized === '/' ? '/' : rtrim($normalized, '/');
    }

    /**
     * @param  list<array<string, mixed>>  $suggestions
     */
    private function writeArtifact(RescuedSite $site, array $suggestions): string
    {
        $filename = Carbon::now()->format('Ymd_His').'_'.Str::slug($site->source_label ?: 'source').'.json';
        $relativePath = trim((string) config('gjallr.wayback.url_suggestions_directory'), '/').'/'.$filename;
        $absolutePath = storage_path('app/'.$relativePath);

        File::ensureDirectoryExists(dirname($absolutePath));
        File::put($absolutePath, json_encode([
            'source_label' => $site->source_label,
            'generated_at' => Carbon::now()->toIso8601String(),
            'suggestions' => $suggestions,
        ], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR));

        return $relativePath;
    }
}
