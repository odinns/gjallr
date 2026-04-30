<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\MediaAsset;
use App\Models\RedirectRule;
use App\Models\RescuedSite;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

final class WaybackRecoveryTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        File::deleteDirectory(storage_path('app/gjallr/recovered-media'));
        File::deleteDirectory(storage_path('app/gjallr/wayback-url-suggestions'));
        config()->set('wayback-machine.user_agent', 'gjallr-tests/1.0');
    }

    public function test_it_recovers_missing_media_from_an_exact_wayback_capture_and_serves_it(): void
    {
        $this->siteWithMedia('2021/05/missing.jpg', 9001);

        Http::fake([
            'web.archive.org/cdx/search/cdx*' => Http::response([
                ['timestamp', 'original', 'statuscode', 'mimetype', 'digest', 'length'],
                ['20200101000000', 'https://example.test/wp-content/uploads/2021/05/missing.jpg', '200', 'image/jpeg', 'abc', '9'],
            ]),
            'web.archive.org/web/*' => Http::response('recovered', 200, ['Content-Type' => 'image/jpeg']),
        ]);

        $exitCode = Artisan::call('gjallr:wayback:recover-media', [
            '--source-label' => 'fixture import',
            '--limit' => 50,
        ]);

        $this->assertSame(0, $exitCode);
        $this->assertSame('recovered', File::get(storage_path('app/gjallr/recovered-media/2021/05/missing.jpg')));

        $this->get('/rescued-media/2021/05/missing.jpg')
            ->assertOk()
            ->assertSee('recovered');
    }

    public function test_it_skips_existing_local_uploads_without_contacting_wayback(): void
    {
        $this->siteWithMedia('2021/05/demo.jpg', 9002);

        Http::fake();

        $exitCode = Artisan::call('gjallr:wayback:recover-media', [
            '--source-label' => 'fixture import',
        ]);

        $this->assertSame(0, $exitCode);
        $this->assertStringContainsString('skipped-local', Artisan::output());

        Http::assertNothingSent();
    }

    public function test_it_dry_runs_media_recovery_without_writing_files(): void
    {
        $this->siteWithMedia('2021/05/dry-run.jpg', 9003);

        Http::fake([
            'web.archive.org/cdx/search/cdx*' => Http::response([
                ['timestamp', 'original', 'statuscode', 'mimetype', 'digest', 'length'],
                ['20200101000000', 'https://example.test/wp-content/uploads/2021/05/dry-run.jpg', '200', 'image/jpeg', 'abc', '9'],
            ]),
        ]);

        $exitCode = Artisan::call('gjallr:wayback:recover-media', [
            '--source-label' => 'fixture import',
            '--dry-run' => true,
        ]);

        $this->assertSame(0, $exitCode);
        $this->assertStringContainsString('planned', Artisan::output());
        $this->assertFalse(File::exists(storage_path('app/gjallr/recovered-media/2021/05/dry-run.jpg')));
    }

    public function test_it_discovers_unmatched_archived_html_paths_without_creating_redirects(): void
    {
        $site = RescuedSite::query()->create([
            'source_label' => 'fixture import',
            'name' => 'Fixture Site',
            'site_url' => 'https://example.test',
            'home_url' => 'https://example.test',
            'show_on_front' => 'posts',
        ]);
        $site->contentItems()->create([
            'source_type' => 'post',
            'original_source_id' => 1,
            'title' => 'Hello World',
            'slug' => 'hello-world',
            'path' => 'hello-world',
        ]);
        RedirectRule::query()->create([
            'rescued_site_id' => $site->id,
            'from_path' => '/legacy-known',
            'to_path' => '/hello-world',
        ]);

        Http::fake([
            'web.archive.org/cdx/search/cdx*' => Http::response([
                ['timestamp', 'original', 'statuscode', 'mimetype', 'digest', 'length'],
                ['20200101000000', 'https://example.test/hello-world', '200', 'text/html', 'abc', '100'],
                ['20200102000000', 'https://example.test/legacy-known', '200', 'text/html', 'def', '100'],
                ['20200103000000', 'https://example.test/orphaned-page/', '200', 'text/html', 'ghi', '100'],
                ['20200104000000', 'https://example.test/wp-content/uploads/demo.jpg', '200', 'text/html', 'jkl', '100'],
            ]),
        ]);

        $exitCode = Artisan::call('gjallr:wayback:discover-urls', [
            '--source-label' => 'fixture import',
            '--limit' => 500,
        ]);

        $this->assertSame(0, $exitCode);
        $this->assertStringContainsString('/orphaned-page', Artisan::output());
        $this->assertFalse(RedirectRule::query()->where('from_path', '/orphaned-page')->exists());

        $artifact = collect(File::files(storage_path('app/gjallr/wayback-url-suggestions')))->first();

        $this->assertNotNull($artifact);

        $payload = json_decode(File::get((string) $artifact), true, 512, JSON_THROW_ON_ERROR);

        $this->assertCount(1, $payload['suggestions']);
        $this->assertSame('/orphaned-page', $payload['suggestions'][0]['path']);
    }

    private function siteWithMedia(string $path, int $sourceId): RescuedSite
    {
        $site = RescuedSite::query()->create([
            'source_label' => 'fixture import',
            'name' => 'Fixture Site',
            'site_url' => 'https://example.test',
            'home_url' => 'https://example.test',
            'show_on_front' => 'posts',
            'site_path' => base_path('tests/Fixtures/wordpress-sample/site'),
        ]);

        MediaAsset::query()->create([
            'rescued_site_id' => $site->id,
            'original_source_id' => $sourceId,
            'path' => $path,
            'url' => '/rescued-media/'.$path,
            'mime_type' => 'image/jpeg',
        ]);

        return $site;
    }
}
