<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\NavigationMenu;
use App\Models\RescuedSite;
use App\Models\Taxonomy;
use Illuminate\Contracts\View\View;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class TaxonomyController extends Controller
{
    public function show(Request $request, string $slug): View|JsonResponse
    {
        $type = (string) $request->route('type', 'category');
        $site = RescuedSite::query()->latest('id')->firstOrFail();
        $menus = NavigationMenu::query()
            ->where('rescued_site_id', $site->id)
            ->with('items.contentItem', 'items.children.contentItem')
            ->get();
        $taxonomy = Taxonomy::query()
            ->where('rescued_site_id', $site->id)
            ->where('type', $type)
            ->where('slug', $slug)
            ->firstOrFail();

        $contentItems = $taxonomy->contentItems()
            ->where('status', 'publish')
            ->orderByDesc('published_at')
            ->get();

        $payload = [
            'taxonomy' => [
                'type' => $taxonomy->type,
                'name' => $taxonomy->name,
                'path' => '/'.$taxonomy->path,
                'description' => $taxonomy->description,
            ],
            'items' => $contentItems->map(fn ($contentItem): array => [
                'title' => $contentItem->title,
                'excerpt' => $contentItem->excerpt,
                'path' => '/'.$contentItem->path,
            ])->all(),
        ];

        if ($request->wantsJson() || $request->query('format') === 'json') {
            return response()->json($payload);
        }

        return view('taxonomy.show', [
            'site' => $site,
            'menus' => $menus,
            'taxonomy' => $taxonomy,
            'contentItems' => $contentItems,
            'pageData' => $payload,
        ]);
    }
}
