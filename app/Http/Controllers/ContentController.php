<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\ContentItem;
use App\Models\NavigationMenu;
use App\Models\RedirectRule;
use App\Models\RescuedSite;
use App\Support\PageDataFactory;
use Illuminate\Contracts\View\View;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class ContentController extends Controller
{
    public function show(Request $request, string $path, PageDataFactory $pageDataFactory): View|JsonResponse|RedirectResponse
    {
        $normalizedPath = trim($path, '/');
        $site = RescuedSite::query()->latest('id')->firstOrFail();
        $menus = NavigationMenu::query()
            ->where('rescued_site_id', $site->id)
            ->with('items.contentItem', 'items.children.contentItem')
            ->get();

        $redirect = RedirectRule::query()
            ->where('rescued_site_id', $site->id)
            ->where('from_path', '/'.$normalizedPath)
            ->first();

        if ($redirect !== null) {
            return redirect($redirect->to_path, 301);
        }

        $contentItem = ContentItem::query()
            ->where('rescued_site_id', $site->id)
            ->where('path', $normalizedPath)
            ->where('status', 'publish')
            ->firstOrFail();

        if ($contentItem->is_posts_index) {
            $posts = ContentItem::query()
                ->where('rescued_site_id', $site->id)
                ->where('source_type', 'post')
                ->where('status', 'publish')
                ->orderByDesc('published_at')
                ->get();

            if ($request->wantsJson() || $request->query('format') === 'json') {
                return response()->json([
                    'page' => [
                        'title' => $contentItem->title,
                        'path' => '/'.$contentItem->path,
                    ],
                    'posts' => $posts->map(fn ($post): array => [
                        'title' => $post->title,
                        'excerpt' => $post->excerpt,
                        'path' => '/'.$post->path,
                    ])->all(),
                ]);
            }

            return view('home.index', [
                'site' => $site,
                'menus' => $menus,
                'posts' => $posts,
                'pageData' => [
                    'page' => [
                        'title' => $contentItem->title,
                        'path' => '/'.$contentItem->path,
                    ],
                ],
            ]);
        }

        $pageData = $pageDataFactory->forContentItem($contentItem, $site, $menus);

        if ($request->wantsJson() || $request->query('format') === 'json') {
            return response()->json($pageData);
        }

        return view('content.show', [
            'site' => $site,
            'menus' => $menus,
            'contentItem' => $contentItem,
            'pageData' => $pageData,
        ]);
    }
}
