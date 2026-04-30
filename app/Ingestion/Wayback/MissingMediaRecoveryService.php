<?php

declare(strict_types=1);

namespace App\Ingestion\Wayback;

use App\Models\MediaAsset;
use App\Models\RescuedSite;
use Illuminate\Support\Facades\File;
use Odinns\LaravelWaybackMachine\CaptureScope;
use Odinns\LaravelWaybackMachine\CdxQuery;
use Odinns\LaravelWaybackMachine\WaybackClient;
use Odinns\LaravelWaybackMachine\WaybackDownloader;

final readonly class MissingMediaRecoveryService
{
    public function __construct(
        private WaybackClient $client,
        private WaybackDownloader $downloader,
        private WaybackOptionsFactory $options,
    ) {}

    /**
     * @return list<array<string, mixed>>
     */
    public function recover(RescuedSite $site, int $limit, bool $dryRun, bool $force): array
    {
        $results = [];
        $assets = MediaAsset::query()
            ->where('rescued_site_id', $site->id)
            ->whereNotNull('path')
            ->orderBy('id')
            ->limit($limit)
            ->get();

        foreach ($assets as $asset) {
            $path = ltrim((string) $asset->path, '/');

            if ($this->localUploadExists($site, $path)) {
                $results[] = $this->result($path, 'skipped-local', null, null);

                continue;
            }

            $originalUrl = $this->originalUrl($site, $path);

            if ($originalUrl === null) {
                $results[] = $this->result($path, 'missing-source-url', null, null);

                continue;
            }

            $captures = $this->client->captures(
                CaptureScope::from($originalUrl, 'exact'),
                new CdxQuery(
                    statuses: [200],
                    limit: 1,
                    pageLimit: (int) config('gjallr.wayback.media_page_limit', 1),
                ),
                $this->options->make(dryRun: $dryRun, force: $force),
            );

            $capture = $captures[0] ?? null;

            if ($capture === null) {
                $results[] = $this->result($path, 'not-found', $originalUrl, null);

                continue;
            }

            $download = $this->downloader->download(
                $capture,
                $this->recoveredPath($path),
                $this->options->make(dryRun: $dryRun, force: $force),
            );

            $results[] = $this->result($path, $download->status, $originalUrl, $download->path);
        }

        return $results;
    }

    public function recoveredPath(string $path): string
    {
        return storage_path('app/'.trim((string) config('gjallr.wayback.recovered_media_directory'), '/').'/'.ltrim($path, '/'));
    }

    private function localUploadExists(RescuedSite $site, string $path): bool
    {
        return $site->site_path !== null
            && File::exists(rtrim($site->site_path, '/').'/wp-content/uploads/'.$path);
    }

    private function originalUrl(RescuedSite $site, string $path): ?string
    {
        $baseUrl = $site->home_url ?: $site->site_url;

        if ($baseUrl === null || trim($baseUrl) === '') {
            return null;
        }

        return rtrim($baseUrl, '/').'/wp-content/uploads/'.$path;
    }

    /**
     * @return array<string, mixed>
     */
    private function result(string $path, string $status, ?string $originalUrl, ?string $localPath): array
    {
        return [
            'path' => $path,
            'public_url' => '/rescued-media/'.$path,
            'original_url' => $originalUrl,
            'local_path' => $localPath,
            'status' => $status,
        ];
    }
}
