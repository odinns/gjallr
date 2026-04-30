<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\RescuedSite;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\File;

class RescuedMediaController extends Controller
{
    public function __invoke(string $path): Response
    {
        $site = RescuedSite::query()->latest('id')->firstOrFail();
        $localUploadPath = $site->site_path !== null
            ? rtrim($site->site_path, '/').'/wp-content/uploads/'.$path
            : null;
        $recoveredPath = storage_path('app/'.trim((string) config('gjallr.wayback.recovered_media_directory'), '/').'/'.$path);

        $absolutePath = match (true) {
            $localUploadPath !== null && File::exists($localUploadPath) => $localUploadPath,
            File::exists($recoveredPath) => $recoveredPath,
            default => null,
        };

        abort_if($absolutePath === null, 404);

        return response(File::get($absolutePath), 200, [
            'Content-Type' => File::mimeType($absolutePath) ?: 'application/octet-stream',
            'Cache-Control' => 'public, max-age=3600',
        ]);
    }
}
