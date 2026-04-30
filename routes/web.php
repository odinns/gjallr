<?php

use App\Http\Controllers\ContentController;
use App\Http\Controllers\HomeController;
use App\Http\Controllers\RescuedMediaController;
use App\Http\Controllers\TaxonomyController;
use Illuminate\Support\Facades\Route;

Route::get('/', HomeController::class)->name('home');
Route::get('/rescued-media/{path}', RescuedMediaController::class)
    ->where('path', '.*')
    ->name('rescued-media');
Route::get('/category/{slug}', [TaxonomyController::class, 'show'])
    ->defaults('type', 'category')
    ->name('taxonomy.category');
Route::get('/tag/{slug}', [TaxonomyController::class, 'show'])
    ->defaults('type', 'tag')
    ->name('taxonomy.tag');
Route::get('/{path}', [ContentController::class, 'show'])
    ->where('path', '.*')
    ->name('content.show');
