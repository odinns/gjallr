<?php

declare(strict_types=1);

namespace App\Ingestion\WordPress\Analysis;

use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;

final class SiteTreeScanner
{
    /**
     * @var array<int, string>
     */
    private array $seoPluginSlugs;

    public function __construct()
    {
        $this->seoPluginSlugs = (array) config('gjallr.analysis.seo_plugin_slugs', []);
    }

    public function scan(string $sitePath): SiteTreeAnalysis
    {
        $versionFile = $sitePath.'/wp-includes/version.php';
        $uploadsPath = $sitePath.'/wp-content/uploads';
        $pluginsPath = $sitePath.'/wp-content/plugins';
        $themesPath = $sitePath.'/wp-content/themes';

        [$coreVersion, $coreDbVersion] = $this->readCoreVersionSignals($versionFile);
        $plugins = $this->scanPlugins($pluginsPath);
        $themes = $this->scanThemes($themesPath);

        return new SiteTreeAnalysis(
            coreVersion: $coreVersion,
            coreDbVersion: $coreDbVersion,
            hasUploads: File::isDirectory($uploadsPath),
            plugins: $plugins,
            themes: $themes,
            seoPluginSlugs: array_values(array_filter(
                array_column($plugins, 'slug'),
                fn (string $slug): bool => in_array($slug, $this->seoPluginSlugs, true),
            )),
        );
    }

    /**
     * @return array{0: string|null, 1: string|null}
     */
    private function readCoreVersionSignals(string $versionFile): array
    {
        if (! File::exists($versionFile)) {
            return [null, null];
        }

        $contents = File::get($versionFile);

        preg_match('/\$wp_version\s*=\s*\'([^\']+)\'/', $contents, $versionMatches);
        preg_match('/\$wp_db_version\s*=\s*(\d+)/', $contents, $dbVersionMatches);

        return [
            $versionMatches[1] ?? null,
            $dbVersionMatches[1] ?? null,
        ];
    }

    /**
     * @return array<int, array{slug: string, name: string|null, version: string|null}>
     */
    private function scanPlugins(string $pluginsPath): array
    {
        if (! File::isDirectory($pluginsPath)) {
            return [];
        }

        $plugins = [];

        foreach (File::directories($pluginsPath) as $pluginDirectory) {
            $slug = basename($pluginDirectory);
            $header = $this->readHeaderMetadata($this->findPrimaryPhpFile($pluginDirectory), 'Plugin Name');

            $plugins[] = [
                'slug' => $slug,
                'name' => $header['name'],
                'version' => $header['version'],
            ];
        }

        return $plugins;
    }

    /**
     * @return array<int, array{slug: string, name: string|null, version: string|null}>
     */
    private function scanThemes(string $themesPath): array
    {
        if (! File::isDirectory($themesPath)) {
            return [];
        }

        $themes = [];

        foreach (File::directories($themesPath) as $themeDirectory) {
            $slug = basename($themeDirectory);
            $header = $this->readHeaderMetadata($themeDirectory.'/style.css', 'Theme Name');

            $themes[] = [
                'slug' => $slug,
                'name' => $header['name'],
                'version' => $header['version'],
            ];
        }

        return $themes;
    }

    private function findPrimaryPhpFile(string $directory): ?string
    {
        $primaryCandidate = $directory.'/'.basename($directory).'.php';

        if (File::exists($primaryCandidate)) {
            return $primaryCandidate;
        }

        $phpFiles = collect(File::files($directory))
            ->filter(fn ($file): bool => $file->getExtension() === 'php')
            ->sortBy(fn ($file): string => $file->getFilename())
            ->values();

        return $phpFiles->isEmpty() ? null : $phpFiles->first()->getPathname();
    }

    /**
     * @return array{name: string|null, version: string|null}
     */
    private function readHeaderMetadata(?string $path, string $nameHeader): array
    {
        if ($path === null || ! File::exists($path)) {
            return ['name' => null, 'version' => null];
        }

        $contents = File::get($path);
        $chunk = Str::of($contents)->substr(0, 8192)->value();

        preg_match('/^'.preg_quote($nameHeader, '/').':\s*(.+)$/mi', $chunk, $nameMatches);
        preg_match('/^Version:\s*(.+)$/mi', $chunk, $versionMatches);

        return [
            'name' => isset($nameMatches[1]) ? trim($nameMatches[1]) : null,
            'version' => isset($versionMatches[1]) ? trim($versionMatches[1]) : null,
        ];
    }
}
