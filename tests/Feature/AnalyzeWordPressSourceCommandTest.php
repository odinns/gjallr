<?php

declare(strict_types=1);

use App\Models\SourceAnalysisRun;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Http;

it('analyzes a wordpress sample and stores a profile artifact', function (): void {
    $sqlDumpPath = base_path('tests/Fixtures/wordpress-sample/sample.sql');
    $sitePath = base_path('tests/Fixtures/wordpress-sample/site');

    $exitCode = Artisan::call('gjallr:analyze-source', [
        '--sql-dump' => $sqlDumpPath,
        '--site-path' => $sitePath,
        '--source-label' => 'fixture sample',
    ]);

    expect($exitCode)->toBe(0)
        ->and(Artisan::output())->toContain('WordPress source profile written.');

    $run = SourceAnalysisRun::query()->latest('id')->firstOrFail();

    expect($run->detected_version)->toBe('5.7.8')
        ->and($run->compatibility_band)->toBe('4.x-6.x')
        ->and($run->detected_prefix)->toBe('wp_')
        ->and($run->plugins_count)->toBe(1)
        ->and($run->themes_count)->toBe(1)
        ->and($run->has_uploads)->toBeTrue()
        ->and($run->suspicious_findings_count)->toBeGreaterThan(0);

    $artifact = storage_path('app/'.$run->artifact_path);

    expect(File::exists($artifact))->toBeTrue();

    $payload = json_decode(File::get($artifact), true, 512, JSON_THROW_ON_ERROR);

    expect($payload['active_theme'])->toBe('sample-theme')
        ->and($payload['capabilities']['menus'])->toBeTrue()
        ->and($payload['seo_plugin_slugs'])->toContain('easy-wp-meta-description');
});

it('leaves wayback alone unless requested', function (): void {
    Http::fake();

    $exitCode = Artisan::call('gjallr:analyze-source', [
        '--sql-dump' => base_path('tests/Fixtures/wordpress-sample/sample.sql'),
        '--site-path' => base_path('tests/Fixtures/wordpress-sample/site'),
        '--source-label' => 'fixture sample',
    ]);

    expect($exitCode)->toBe(0);

    Http::assertNothingSent();
});

it('stores a bounded wayback availability summary when requested', function (): void {
    config()->set('wayback-machine.user_agent', 'gjallr-tests/1.0');
    config()->set('gjallr.wayback.analysis_limit', 2);

    Http::fake([
        'web.archive.org/cdx/search/cdx*' => Http::response([
            ['timestamp', 'original', 'statuscode', 'mimetype', 'digest', 'length'],
            ['20200101000000', 'https://example.test/', '200', 'text/html', 'abc', '100'],
            ['20210101000000', 'https://example.test/about', '200', 'text/html', 'def', '200'],
        ]),
    ]);

    $exitCode = Artisan::call('gjallr:analyze-source', [
        '--sql-dump' => base_path('tests/Fixtures/wordpress-sample/sample.sql'),
        '--site-path' => base_path('tests/Fixtures/wordpress-sample/site'),
        '--source-label' => 'fixture sample',
        '--with-wayback' => true,
    ]);

    expect($exitCode)->toBe(0);

    $run = SourceAnalysisRun::query()->latest('id')->firstOrFail();
    $artifact = json_decode(File::get(storage_path('app/'.$run->artifact_path)), true, 512, JSON_THROW_ON_ERROR);

    expect($artifact['wayback']['captures_count'])->toBe(2)
        ->and($artifact['wayback']['first_capture_at'])->toBe('20200101000000')
        ->and($artifact['wayback']['last_capture_at'])->toBe('20210101000000')
        ->and($run->summary_json['wayback']['captures_count'])->toBe(2);

    Http::assertSent(fn ($request): bool => str_starts_with($request->url(), 'https://web.archive.org/cdx/search/cdx')
        && $request['matchType'] === 'host'
        && $request['limit'] === 2);
});
