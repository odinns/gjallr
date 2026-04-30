<?php

declare(strict_types=1);

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Tests\TestCase;

final class RescueRuntimeTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        DB::connection('wordpress')->unprepared(File::get(base_path('tests/Fixtures/wordpress-import-fixture.sql')));

        Artisan::call('gjallr:import', [
            '--sql-dump' => base_path('tests/Fixtures/wordpress-sample/sample.sql'),
            '--site-path' => base_path('tests/Fixtures/wordpress-sample/site'),
            '--source-label' => 'fixture import',
        ]);
    }

    public function test_it_renders_the_rescued_homepage_and_content_page(): void
    {
        $this->get('/')
            ->assertOk()
            ->assertSee('Front Page')
            ->assertSee('Fixture Site');

        $this->get('/hello-world')
            ->assertOk()
            ->assertSee('Hello World')
            ->assertSee('First comment')
            ->assertSee('/rescued-media/2021/05/demo.jpg');
    }

    public function test_it_renders_taxonomy_pages_and_json_page_data(): void
    {
        $this->get('/category/news')
            ->assertOk()
            ->assertSee('News')
            ->assertSee('Hello World');

        $this->get('/hello-world?format=json')
            ->assertOk()
            ->assertJsonPath('page.title', 'Hello World')
            ->assertJsonPath('page.media.0.url', '/rescued-media/2021/05/demo.jpg');
    }

    public function test_it_exposes_rescued_private_and_draft_content_on_their_new_public_paths(): void
    {
        $this->get('/private-notes')
            ->assertOk()
            ->assertSee('Private Notes');

        $this->get('/draft-working-draft')
            ->assertOk()
            ->assertSee('Working Draft');

        $this->get('/working-draft')->assertNotFound();
    }

    public function test_it_renders_augmented_nested_menu_markup(): void
    {
        $this->get('/')
            ->assertOk()
            ->assertSee('Behandlinger')
            ->assertSee('Lokalet')
            ->assertSee('<ul>', false)
            ->assertDontSee('<ol>', false);
    }
}
