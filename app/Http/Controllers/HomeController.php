<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\ContentItem;
use App\Models\NavigationMenu;
use App\Models\RescuedSite;
use App\Support\PageDataFactory;
use Illuminate\Contracts\View\View;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class HomeController extends Controller
{
    public function __invoke(Request $request, PageDataFactory $pageDataFactory): View|JsonResponse
    {
        $site = RescuedSite::query()->latest('id')->firstOrFail();
        $menus = NavigationMenu::query()
            ->where('rescued_site_id', $site->id)
            ->with('items.contentItem', 'items.children.contentItem')
            ->get();

        if ($site->show_on_front === 'page' && $site->page_on_front_source_id !== null) {
            $frontPage = ContentItem::query()
                ->where('rescued_site_id', $site->id)
                ->where('original_source_id', $site->page_on_front_source_id)
                ->where('status', 'publish')
                ->firstOrFail();

            $pageData = $pageDataFactory->forContentItem($frontPage, $site, $menus);

            if ($request->wantsJson() || $request->query('format') === 'json') {
                return response()->json($pageData);
            }

            return view('content.show', [
                'site' => $site,
                'menus' => $menus,
                'contentItem' => $frontPage,
                'pageData' => $pageData,
            ]);
        }

        $posts = ContentItem::query()
            ->where('rescued_site_id', $site->id)
            ->where('source_type', 'post')
            ->where('status', 'publish')
            ->orderByDesc('published_at')
            ->get();

        if ($request->wantsJson() || $request->query('format') === 'json') {
            return response()->json([
                'site' => [
                    'name' => $site->name,
                    'home_url' => $site->home_url,
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
            'pageData' => null,
        ]);
    }
}
