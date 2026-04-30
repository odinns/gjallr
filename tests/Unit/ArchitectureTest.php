<?php

declare(strict_types=1);

test('domain code does not depend on ingestion', function (): void {
    expect('App\Domain')->not->toUse(['App\Ingestion', 'Odinns\LaravelWaybackMachine']);
});

test('transformation code does not depend on wordpress internals', function (): void {
    expect('App\Transformation')->not->toUse([
        'App\Ingestion\WordPress',
        'App\Ingestion\Wayback',
        'Odinns\LaravelWaybackMachine',
        'wp_',
        'wordpress',
    ]);
});
