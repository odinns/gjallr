<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Ingestion\Wayback\ArchivedUrlDiscoveryService;
use App\Models\RescuedSite;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;

#[Signature('gjallr:wayback:discover-urls
    {--source-label= : Source label to inspect}
    {--limit=500 : Maximum archived HTML captures to inspect}
    {--dry-run : Report suggestions without writing an artifact}')]
#[Description('Suggest archived HTML URLs that are missing from the rescued runtime')]
class DiscoverWaybackUrlsCommand extends Command
{
    public function handle(ArchivedUrlDiscoveryService $discovery): int
    {
        $site = $this->site();

        if (! $site instanceof RescuedSite) {
            $this->error('No rescued site found for that source label.');

            return self::FAILURE;
        }

        $result = $discovery->discover(
            site: $site,
            limit: max(1, (int) $this->option('limit')),
            dryRun: (bool) $this->option('dry-run'),
        );

        $this->table(
            ['Path', 'Timestamp', 'Archived URL'],
            array_map(fn (array $suggestion): array => [
                $suggestion['path'],
                $suggestion['timestamp'] ?? '-',
                $suggestion['archived_url'] ?? '-',
            ], $result['suggestions']),
        );

        if ($result['artifact_path'] !== null) {
            $this->components->info('Suggestions written to '.$result['artifact_path']);
        }

        return self::SUCCESS;
    }

    private function site(): ?RescuedSite
    {
        $label = $this->option('source-label');

        return RescuedSite::query()
            ->when(is_string($label) && $label !== '', fn ($query) => $query->where('source_label', $label))
            ->latest('id')
            ->first();
    }
}
