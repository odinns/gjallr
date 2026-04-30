<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;

it('writes a build dossier for human and ai reconstruction', function (): void {
    DB::connection('wordpress')->unprepared(File::get(base_path('tests/Fixtures/wordpress-import-fixture.sql')));

    Artisan::call('gjallr:import', [
        '--sql-dump' => base_path('tests/Fixtures/wordpress-sample/sample.sql'),
        '--site-path' => base_path('tests/Fixtures/wordpress-sample/site'),
        '--source-label' => 'fixture import',
    ]);

    File::deleteDirectory(storage_path('app/gjallr/build'));

    $exitCode = Artisan::call('gjallr:build', [
        '--source-label' => 'fixture import',
        '--format' => 'both',
    ]);

    expect($exitCode)->toBe(0)
        ->and(Artisan::output())->toContain('Build dossier written.');

    $jsonArtifact = collect(File::files(storage_path('app/gjallr/build')))
        ->first(fn ($file): bool => $file->getExtension() === 'json');
    $markdownArtifact = collect(File::files(storage_path('app/gjallr/build')))
        ->first(fn ($file): bool => $file->getExtension() === 'md');

    expect($jsonArtifact)->not->toBeNull()
        ->and($markdownArtifact)->not->toBeNull();

    $payload = json_decode(File::get((string) $jsonArtifact), true, 512, JSON_THROW_ON_ERROR);

    expect($payload['source_label'])->toBe('fixture import')
        ->and($payload['counts']['content_items'])->toBe(7)
        ->and($payload['builder_notes'][0])->toContain('evidence');

    expect(collect($payload['routes'])->pluck('path'))->toContain('/hello-world');

    expect(File::get((string) $markdownArtifact))->toContain('# Gjallr Build Dossier');
});
