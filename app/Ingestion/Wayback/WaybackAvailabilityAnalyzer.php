<?php

declare(strict_types=1);

namespace App\Ingestion\Wayback;

use Odinns\LaravelWaybackMachine\CaptureScope;
use Odinns\LaravelWaybackMachine\CdxCapture;
use Odinns\LaravelWaybackMachine\CdxQuery;
use Odinns\LaravelWaybackMachine\WaybackClient;
use Throwable;

final readonly class WaybackAvailabilityAnalyzer
{
    public function __construct(
        private WaybackClient $client,
        private WaybackOptionsFactory $options,
    ) {}

    /**
     * @return array<string, mixed>
     */
    public function summarize(?string $homeUrl, ?string $siteUrl): array
    {
        $scopeUrl = $this->preferredUrl($homeUrl, $siteUrl);

        if ($scopeUrl === null) {
            return [
                'enabled' => true,
                'available' => false,
                'scope' => null,
                'captures_count' => 0,
                'first_capture_at' => null,
                'last_capture_at' => null,
                'sampled_captures' => [],
                'errors' => ['No home_url or site_url was detected.'],
            ];
        }

        try {
            $captures = $this->client->captures(
                CaptureScope::from($scopeUrl, 'host'),
                new CdxQuery(
                    statuses: [200],
                    limit: (int) config('gjallr.wayback.analysis_limit', 100),
                    pageLimit: (int) config('gjallr.wayback.analysis_page_limit', 1),
                ),
                $this->options->make(),
            );
        } catch (Throwable $throwable) {
            return [
                'enabled' => true,
                'available' => false,
                'scope' => $scopeUrl,
                'captures_count' => 0,
                'first_capture_at' => null,
                'last_capture_at' => null,
                'sampled_captures' => [],
                'errors' => [$throwable->getMessage()],
            ];
        }

        $timestamps = array_values(array_filter(array_map(
            fn (CdxCapture $capture): ?string => $capture->timestamp,
            $captures,
        )));
        sort($timestamps);

        return [
            'enabled' => true,
            'available' => $captures !== [],
            'scope' => $scopeUrl,
            'captures_count' => count($captures),
            'first_capture_at' => $timestamps[0] ?? null,
            'last_capture_at' => $timestamps[array_key_last($timestamps)] ?? null,
            'sampled_captures' => array_map(
                fn (CdxCapture $capture): array => [
                    'timestamp' => $capture->timestamp,
                    'url' => $capture->originalUrl,
                    'status' => $capture->status,
                    'mime' => $capture->mimeType,
                ],
                array_slice($captures, 0, 5),
            ),
            'errors' => [],
        ];
    }

    private function preferredUrl(?string $homeUrl, ?string $siteUrl): ?string
    {
        foreach ([$homeUrl, $siteUrl] as $url) {
            if (is_string($url) && trim($url) !== '') {
                return $url;
            }
        }

        return null;
    }
}
