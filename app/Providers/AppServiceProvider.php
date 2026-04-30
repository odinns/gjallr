<?php

declare(strict_types=1);

namespace App\Providers;

use Illuminate\Support\Facades\Config;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        Config::set('database.connections.wordpress', [
            'driver' => 'mysql',
            'host' => config('gjallr.source_database.host'),
            'port' => config('gjallr.source_database.port'),
            'database' => config('gjallr.source_database.database'),
            'username' => config('gjallr.source_database.username'),
            'password' => config('gjallr.source_database.password'),
            'unix_socket' => config('gjallr.source_database.unix_socket'),
            'charset' => config('gjallr.source_database.charset'),
            'collation' => config('gjallr.source_database.collation'),
            'prefix' => '',
            'prefix_indexes' => true,
            'strict' => true,
            'engine' => null,
        ]);
    }

    public function boot(): void {}
}
