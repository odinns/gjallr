<?php

declare(strict_types=1);

namespace App\Ingestion\Wayback;

use Odinns\LaravelWaybackMachine\WaybackOptions;

final class WaybackOptionsFactory
{
    public function make(bool $dryRun = false, bool $force = false): WaybackOptions
    {
        return new WaybackOptions(
            timeout: (int) config('wayback-machine.timeout', 60),
            delayMs: (int) config('gjallr.wayback.request_delay_ms', 0),
            userAgent: (string) config('wayback-machine.user_agent'),
            force: $force,
            dryRun: $dryRun,
            selection: 'latest-per-url',
            retryBackoffMs: (array) config('wayback-machine.retry_backoff_ms', [1000, 3000, 10000, 30000]),
        );
    }
}
