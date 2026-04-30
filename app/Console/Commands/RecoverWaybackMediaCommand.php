<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Ingestion\Wayback\MissingMediaRecoveryService;
use App\Models\RescuedSite;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;

#[Signature('gjallr:wayback:recover-media
    {--source-label= : Source label to recover media for}
    {--limit=50 : Maximum media assets to inspect}
    {--dry-run : Report planned recovery without writing files}
    {--force : Overwrite existing recovered files}')]
#[Description('Recover missing WordPress uploads from exact Wayback captures')]
class RecoverWaybackMediaCommand extends Command
{
    public function handle(MissingMediaRecoveryService $recovery): int
    {
        $site = $this->site();

        if (! $site instanceof RescuedSite) {
            $this->error('No rescued site found for that source label.');

            return self::FAILURE;
        }

        $results = $recovery->recover(
            site: $site,
            limit: max(1, (int) $this->option('limit')),
            dryRun: (bool) $this->option('dry-run'),
            force: (bool) $this->option('force'),
        );

        $this->table(
            ['Status', 'Path', 'Original URL'],
            array_map(fn (array $result): array => [
                $result['status'],
                $result['path'],
                $result['original_url'] ?? '-',
            ], $results),
        );

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
