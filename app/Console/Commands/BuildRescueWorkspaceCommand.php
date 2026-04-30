<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Models\MediaAsset;
use App\Models\RedirectRule;
use App\Models\RescuedSite;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;

#[Signature('gjallr:build
    {--source-label= : Source label to build a reconstruction dossier for}
    {--format=both : Output format: json, markdown, or both}')]
#[Description('Write a reconstruction dossier for turning rescued data into a real Laravel app')]
class BuildRescueWorkspaceCommand extends Command
{
    public function handle(): int
    {
        $site = $this->site();

        if (! $site instanceof RescuedSite) {
            $this->error('No rescued site found for that source label.');

            return self::FAILURE;
        }

        $format = (string) $this->option('format');

        if (! in_array($format, ['json', 'markdown', 'both'], true)) {
            $this->error('Invalid format. Use json, markdown, or both.');

            return self::FAILURE;
        }

        $dossier = $this->dossier($site);
        $paths = $this->writeDossier($site, $dossier, $format);

        $this->components->info('Build dossier written.');

        foreach ($paths as $path) {
            $this->line('- '.$path);
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

    /**
     * @return array<string, mixed>
     */
    private function dossier(RescuedSite $site): array
    {
        $contentItems = $site->contentItems()
            ->withCount(['comments', 'mediaAssets'])
            ->orderBy('path')
            ->get();
        $mediaAssets = MediaAsset::query()
            ->where('rescued_site_id', $site->id)
            ->orderBy('path')
            ->get();
        $redirects = RedirectRule::query()
            ->where('rescued_site_id', $site->id)
            ->orderBy('from_path')
            ->get();

        return [
            'source_label' => $site->source_label,
            'generated_at' => Carbon::now()->toIso8601String(),
            'site' => [
                'name' => $site->name,
                'home_url' => $site->home_url,
                'site_url' => $site->site_url,
                'permalink_structure' => $site->permalink_structure,
                'active_theme' => $site->active_theme,
            ],
            'counts' => [
                'content_items' => $contentItems->count(),
                'taxonomies' => $site->taxonomies()->count(),
                'comments' => $site->contentItems()->withCount('comments')->get()->sum('comments_count'),
                'media_assets' => $mediaAssets->count(),
                'navigation_menus' => $site->navigationMenus()->count(),
                'redirects' => $redirects->count(),
            ],
            'routes' => $contentItems->map(fn ($item): array => [
                'path' => '/'.$item->path,
                'title' => $item->title,
                'type' => $item->source_type,
                'status' => $item->status,
                'comments' => $item->comments_count,
                'media' => $item->media_assets_count,
                'seo_title' => $item->seo_title,
                'seo_description' => $item->seo_description,
            ])->values()->all(),
            'taxonomies' => $site->taxonomies()
                ->orderBy('path')
                ->get()
                ->map(fn ($taxonomy): array => [
                    'path' => '/'.$taxonomy->path,
                    'type' => $taxonomy->type,
                    'name' => $taxonomy->name,
                ])
                ->values()
                ->all(),
            'media' => $mediaAssets->map(fn ($media): array => [
                'path' => $media->path,
                'public_url' => $media->url,
                'mime_type' => $media->mime_type,
                'present_locally' => $this->mediaExists($site, (string) $media->path),
            ])->values()->all(),
            'redirects' => $redirects->map(fn ($redirect): array => [
                'from' => $redirect->from_path,
                'to' => $redirect->to_path,
            ])->values()->all(),
            'builder_notes' => [
                'Build from this evidence, not from WordPress behavior.',
                'Keep imported content as source material. Rewrite the Laravel app deliberately.',
                'Use Wayback suggestions as leads only. Do not create redirects without review.',
            ],
        ];
    }

    private function mediaExists(RescuedSite $site, string $path): bool
    {
        if ($path === '') {
            return false;
        }

        $localPath = $site->site_path !== null
            ? rtrim($site->site_path, '/').'/wp-content/uploads/'.ltrim($path, '/')
            : null;
        $recoveredPath = storage_path('app/'.trim((string) config('gjallr.wayback.recovered_media_directory'), '/').'/'.ltrim($path, '/'));

        return ($localPath !== null && File::exists($localPath)) || File::exists($recoveredPath);
    }

    /**
     * @param  array<string, mixed>  $dossier
     * @return list<string>
     */
    private function writeDossier(RescuedSite $site, array $dossier, string $format): array
    {
        $base = trim((string) config('gjallr.build.artifact_directory'), '/');
        $name = Carbon::now()->format('Ymd_His').'_'.Str::slug($site->source_label ?: 'site');
        $paths = [];

        if ($format === 'json' || $format === 'both') {
            $paths[] = $this->write($base.'/'.$name.'.json', json_encode($dossier, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR));
        }

        if ($format === 'markdown' || $format === 'both') {
            $paths[] = $this->write($base.'/'.$name.'.md', $this->markdown($dossier));
        }

        return $paths;
    }

    private function write(string $relativePath, string $contents): string
    {
        $absolutePath = storage_path('app/'.$relativePath);

        File::ensureDirectoryExists(dirname($absolutePath));
        File::put($absolutePath, $contents);

        return $relativePath;
    }

    /**
     * @param  array<string, mixed>  $dossier
     */
    private function markdown(array $dossier): string
    {
        $lines = [
            '# Gjallr Build Dossier',
            '',
            'Source: '.($dossier['source_label'] ?? 'unknown'),
            'Generated: '.($dossier['generated_at'] ?? 'unknown'),
            '',
            '## Counts',
            '',
        ];

        foreach ((array) $dossier['counts'] as $key => $value) {
            $lines[] = '- '.str_replace('_', ' ', (string) $key).': '.$value;
        }

        $lines[] = '';
        $lines[] = '## Routes';
        $lines[] = '';

        foreach (array_slice((array) $dossier['routes'], 0, 100) as $route) {
            if (! is_array($route)) {
                continue;
            }

            $lines[] = '- '.($route['path'] ?? '/').' - '.($route['title'] ?? 'Untitled');
        }

        $lines[] = '';
        $lines[] = '## Builder Notes';
        $lines[] = '';

        foreach ((array) $dossier['builder_notes'] as $note) {
            $lines[] = '- '.$note;
        }

        return implode("\n", $lines)."\n";
    }
}
