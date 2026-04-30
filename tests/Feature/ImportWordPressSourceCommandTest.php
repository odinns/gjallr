<?php

declare(strict_types=1);

use App\Models\ContentItem;
use App\Models\NavigationItem;
use App\Models\NavigationMenu;
use App\Models\RescuedSite;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;

it('imports wordpress source tables into the rescue runtime', function (): void {
    DB::connection('wordpress')->unprepared(File::get(base_path('tests/Fixtures/wordpress-import-fixture.sql')));

    $exitCode = Artisan::call('gjallr:import', [
        '--site-path' => base_path('tests/Fixtures/wordpress-sample/site'),
        '--source-label' => 'fixture import',
    ]);

    expect($exitCode)->toBe(0);

    $site = RescuedSite::query()->where('source_label', 'fixture import')->firstOrFail();

    expect($site->name)->toBe('Fixture Site')
        ->and($site->show_on_front)->toBe('page');

    expect(ContentItem::query()->where('rescued_site_id', $site->id)->count())->toBe(7)
        ->and(ContentItem::query()->where('rescued_site_id', $site->id)->where('path', 'hello-world')->exists())->toBeTrue()
        ->and(ContentItem::query()->where('rescued_site_id', $site->id)->where('path', 'draft-working-draft')->where('status', 'publish')->exists())->toBeTrue()
        ->and(ContentItem::query()->where('rescued_site_id', $site->id)->where('path', 'private-notes')->where('status', 'publish')->exists())->toBeTrue()
        ->and(ContentItem::query()->where('rescued_site_id', $site->id)->where('is_posts_index', true)->exists())->toBeTrue()
        ->and(NavigationMenu::query()->where('rescued_site_id', $site->id)->count())->toBe(1)
        ->and(NavigationItem::query()->whereHas('contentItem', fn ($query) => $query->where('path', 'behandlinger'))->exists())->toBeTrue()
        ->and(NavigationItem::query()->whereHas('contentItem', fn ($query) => $query->where('path', 'behandlinger/lokalet'))->whereNotNull('parent_id')->exists())->toBeTrue();
});
