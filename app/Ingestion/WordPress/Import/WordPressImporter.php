<?php

declare(strict_types=1);

namespace App\Ingestion\WordPress\Import;

use App\Ingestion\WordPress\Analysis\AnalyzedWordPressSource;
use App\Models\Comment;
use App\Models\ContentItem;
use App\Models\ImportRun;
use App\Models\MediaAsset;
use App\Models\NavigationItem;
use App\Models\NavigationMenu;
use App\Models\RedirectRule;
use App\Models\RescuedSite;
use App\Models\Taxonomy;
use Carbon\CarbonImmutable;
use Illuminate\Database\Query\Builder as QueryBuilder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

final class WordPressImporter
{
    private string $sourceConnection = 'wordpress';

    public function import(ImportRun $importRun, AnalyzedWordPressSource $analysis): RescuedSite
    {
        $prefix = $analysis->detectedPrefix ?? 'wp_';

        return DB::transaction(function () use ($analysis, $importRun, $prefix): RescuedSite {
            $site = RescuedSite::query()->updateOrCreate(
                ['source_label' => $analysis->sourceLabel],
                [
                    'import_run_id' => $importRun->id,
                    'name' => $this->optionValue($prefix, 'blogname'),
                    'site_url' => $analysis->siteUrl,
                    'home_url' => $analysis->homeUrl,
                    'permalink_structure' => $analysis->permalinkStructure,
                    'active_theme' => $analysis->activeTheme,
                    'source_prefix' => $prefix,
                    'show_on_front' => $this->optionValue($prefix, 'show_on_front') ?: 'posts',
                    'page_on_front_source_id' => $this->toNullableInt($this->optionValue($prefix, 'page_on_front')),
                    'page_for_posts_source_id' => $this->toNullableInt($this->optionValue($prefix, 'page_for_posts')),
                    'site_path' => $analysis->sitePath,
                    'summary_json' => [
                        'version' => $analysis->detectedVersion,
                        'db_version' => $analysis->detectedDbVersion,
                        'capabilities' => $analysis->capabilities,
                        'suspicious_findings' => $analysis->suspiciousFindings,
                    ],
                ],
            );

            $this->clearRuntimeData($site);

            $posts = collect($this->sourceQuery($prefix.'posts')
                ->whereIn('post_type', ['post', 'page'])
                ->whereIn('post_status', ['publish', 'future', 'private', 'draft', 'pending'])
                ->orderBy('ID')
                ->get());

            $postMeta = $this->groupPostMeta($prefix, $posts->pluck('ID')->all());
            $slugMap = $this->buildImportSlugMap($posts);
            $pathMap = $this->buildContentPathMap($posts, $slugMap);

            foreach ($posts as $post) {
                $meta = $postMeta[(int) $post->ID] ?? [];
                $path = $pathMap[(int) $post->ID] ?? ($slugMap[(int) $post->ID] ?? (string) $post->post_name);
                $bodyHtml = $this->rewriteContentHtml(
                    html: (string) $post->post_content,
                    homeUrl: $analysis->homeUrl,
                    siteUrl: $analysis->siteUrl,
                );

                ContentItem::query()->create([
                    'rescued_site_id' => $site->id,
                    'source_type' => (string) $post->post_type,
                    'original_source_id' => (int) $post->ID,
                    'title' => $post->post_title !== '' ? (string) $post->post_title : null,
                    'slug' => $slugMap[(int) $post->ID] ?? (string) $post->post_name,
                    'path' => $path,
                    'excerpt' => $post->post_excerpt !== '' ? (string) $post->post_excerpt : null,
                    'body_html' => $bodyHtml !== '' ? $bodyHtml : null,
                    'status' => $this->normalizedRuntimeStatus($post),
                    'published_at' => $this->normalizeDate($post->post_date_gmt, $post->post_date),
                    'source_parent_id' => (int) $post->post_parent ?: null,
                    'menu_order' => (int) $post->menu_order,
                    'seo_title' => $this->firstMetaValue($meta, ['_yoast_wpseo_title', 'title_tag']) ?? ($post->post_title !== '' ? (string) $post->post_title : null),
                    'seo_description' => $this->firstMetaValue($meta, ['_yoast_wpseo_metadesc', 'meta_description', '_aioseop_description']),
                    'is_posts_index' => (int) $post->ID === (int) ($site->page_for_posts_source_id ?? 0),
                ]);
            }

            $contentItems = ContentItem::query()->where('rescued_site_id', $site->id)->get()->keyBy('original_source_id');

            $attachments = collect($this->sourceQuery($prefix.'posts')
                ->where('post_type', 'attachment')
                ->orderBy('ID')
                ->get());
            $attachmentMeta = $this->groupPostMeta($prefix, $attachments->pluck('ID')->all());

            foreach ($attachments as $attachment) {
                $meta = $attachmentMeta[(int) $attachment->ID] ?? [];
                $relativePath = $meta['_wp_attached_file'][0] ?? null;
                $parentContent = $contentItems->get((int) $attachment->post_parent);

                MediaAsset::query()->create([
                    'rescued_site_id' => $site->id,
                    'original_source_id' => (int) $attachment->ID,
                    'content_item_id' => $parentContent?->id,
                    'path' => $relativePath,
                    'url' => $relativePath !== null ? '/rescued-media/'.ltrim($relativePath, '/') : ($attachment->guid !== '' ? (string) $attachment->guid : null),
                    'mime_type' => $attachment->post_mime_type !== '' ? (string) $attachment->post_mime_type : null,
                    'alt_text' => $meta['_wp_attachment_image_alt'][0] ?? null,
                ]);
            }

            $termTaxonomies = collect($this->sourceQuery($prefix.'term_taxonomy as tt')
                ->join($prefix.'terms as t', 't.term_id', '=', 'tt.term_id')
                ->whereIn('tt.taxonomy', ['category', 'post_tag'])
                ->select([
                    'tt.term_taxonomy_id',
                    'tt.term_id',
                    'tt.taxonomy',
                    'tt.description',
                    'tt.parent',
                    't.name',
                    't.slug',
                ])
                ->get());

            foreach ($termTaxonomies as $row) {
                Taxonomy::query()->create([
                    'rescued_site_id' => $site->id,
                    'original_term_taxonomy_id' => (int) $row->term_taxonomy_id,
                    'original_term_id' => (int) $row->term_id,
                    'type' => $row->taxonomy === 'category' ? 'category' : 'tag',
                    'name' => (string) $row->name,
                    'slug' => (string) $row->slug,
                    'path' => ($row->taxonomy === 'category' ? 'category/' : 'tag/').$row->slug,
                    'description' => $row->description !== '' ? (string) $row->description : null,
                    'source_parent_term_id' => (int) $row->parent ?: null,
                ]);
            }

            $taxonomyMap = Taxonomy::query()->where('rescued_site_id', $site->id)->get()->keyBy('original_term_taxonomy_id');

            $relationships = collect($this->sourceQuery($prefix.'term_relationships')->get());

            foreach ($relationships as $relationship) {
                $contentItem = $contentItems->get((int) $relationship->object_id);
                $taxonomy = $taxonomyMap->get((int) $relationship->term_taxonomy_id);

                if ($contentItem !== null && $taxonomy !== null) {
                    DB::table('content_taxonomy')->insertOrIgnore([
                        'content_item_id' => $contentItem->id,
                        'taxonomy_id' => $taxonomy->id,
                    ]);
                }
            }

            $comments = collect($this->sourceQuery($prefix.'comments')
                ->where('comment_approved', '1')
                ->orderBy('comment_ID')
                ->get());

            foreach ($comments as $sourceComment) {
                $contentItem = $contentItems->get((int) $sourceComment->comment_post_ID);

                if ($contentItem === null) {
                    continue;
                }

                Comment::query()->create([
                    'content_item_id' => $contentItem->id,
                    'original_source_id' => (int) $sourceComment->comment_ID,
                    'source_parent_id' => (int) $sourceComment->comment_parent ?: null,
                    'author_name' => $sourceComment->comment_author !== '' ? (string) $sourceComment->comment_author : null,
                    'author_email' => $sourceComment->comment_author_email !== '' ? (string) $sourceComment->comment_author_email : null,
                    'body' => (string) $sourceComment->comment_content,
                    'created_at' => $this->normalizeDate($sourceComment->comment_date_gmt, $sourceComment->comment_date),
                    'updated_at' => $this->normalizeDate($sourceComment->comment_date_gmt, $sourceComment->comment_date),
                ]);
            }

            /** @var array<int, Comment> $commentMap */
            $commentMap = [];

            $importedComments = Comment::query()
                ->whereIn('content_item_id', $contentItems->pluck('id'))
                ->get();

            foreach ($importedComments as $comment) {
                $commentMap[$comment->original_source_id] = $comment;
            }

            foreach ($commentMap as $comment) {
                if ($comment->source_parent_id === null) {
                    continue;
                }

                $parent = $commentMap[$comment->source_parent_id] ?? null;

                $comment->parent_id = $parent?->id;
                $comment->save();
            }

            $this->importMenus($site, $prefix, $contentItems);
            $this->augmentMenusWithPublishedPageTrees($site);
            $this->resolveFrontPage($site);
            $this->createLegacyQueryRedirects($site, $contentItems);

            $importRun->forceFill([
                'status' => 'completed',
                'detected_prefix' => $prefix,
                'summary_json' => [
                    'content_items' => $contentItems->count(),
                    'taxonomies' => $taxonomyMap->count(),
                    'comments' => count($commentMap),
                    'media_assets' => $attachments->count(),
                    'menus' => NavigationMenu::query()->where('rescued_site_id', $site->id)->count(),
                ],
                'imported_at' => CarbonImmutable::now(),
            ])->save();

            return $site->fresh();
        });
    }

    private function clearRuntimeData(RescuedSite $site): void
    {
        $site->navigationMenus()->delete();
        $site->contentItems()->delete();
        Taxonomy::query()->where('rescued_site_id', $site->id)->delete();
        MediaAsset::query()->where('rescued_site_id', $site->id)->delete();
        RedirectRule::query()->where('rescued_site_id', $site->id)->delete();
    }

    /**
     * @param  array<int>  $postIds
     * @return array<int, array<string, list<string>>>
     */
    private function groupPostMeta(string $prefix, array $postIds): array
    {
        if ($postIds === []) {
            return [];
        }

        $grouped = [];
        $rows = $this->sourceQuery($prefix.'postmeta')
            ->whereIn('post_id', $postIds)
            ->orderBy('meta_id')
            ->get();

        foreach ($rows as $row) {
            $grouped[(int) $row->post_id][(string) $row->meta_key][] = (string) $row->meta_value;
        }

        return $grouped;
    }

    /**
     * @param  Collection<int, object>  $posts
     * @return array<int, string>
     */
    private function buildImportSlugMap(Collection $posts): array
    {
        $slugs = [];

        foreach ($posts as $post) {
            $slugs[(int) $post->ID] = $this->normalizedSlug($post);
        }

        return $slugs;
    }

    /**
     * @param  Collection<int, object>  $posts
     * @param  array<int, string>  $slugMap
     * @return array<int, string>
     */
    private function buildContentPathMap(Collection $posts, array $slugMap): array
    {
        $byId = $posts->keyBy('ID');
        $paths = [];

        $resolve = function (object $post) use (&$resolve, $byId, $slugMap, &$paths): string {
            $id = (int) $post->ID;

            if (isset($paths[$id])) {
                return $paths[$id];
            }

            $slug = $slugMap[$id] ?? ('item-'.$id);
            $parentId = (int) $post->post_parent;

            if ($post->post_type === 'page' && $parentId !== 0 && $byId->has($parentId)) {
                $paths[$id] = trim($resolve($byId->get($parentId)).'/'.$slug, '/');
            } else {
                $paths[$id] = trim($slug, '/');
            }

            return $paths[$id];
        };

        foreach ($posts as $post) {
            $resolve($post);
        }

        return $paths;
    }

    private function normalizedSlug(object $post): string
    {
        $baseSlug = $post->post_name !== '' ? (string) $post->post_name : 'item-'.(int) $post->ID;

        if ($post->post_status !== 'draft') {
            return $baseSlug;
        }

        return Str::startsWith($baseSlug, 'draft-') ? $baseSlug : 'draft-'.$baseSlug;
    }

    private function normalizedRuntimeStatus(object $post): string
    {
        $status = (string) $post->post_status;

        if ($status === 'draft') {
            return 'publish';
        }

        if ($status === 'private' && $post->post_type === 'page') {
            return 'publish';
        }

        return $status;
    }

    /**
     * @param  array<string, list<string>>  $meta
     * @param  array<int, string>  $keys
     */
    private function firstMetaValue(array $meta, array $keys): ?string
    {
        foreach ($keys as $key) {
            $value = $meta[$key][0] ?? null;

            if ($value !== null && $value !== '') {
                return $value;
            }
        }

        return null;
    }

    private function normalizeDate(?string $gmtDate, ?string $localDate): ?CarbonImmutable
    {
        $candidate = ($gmtDate !== null && $gmtDate !== '0000-00-00 00:00:00' && $gmtDate !== '') ? $gmtDate : $localDate;

        if ($candidate === null || $candidate === '' || $candidate === '0000-00-00 00:00:00') {
            return null;
        }

        return CarbonImmutable::parse($candidate, 'UTC');
    }

    private function optionValue(string $prefix, string $optionName): ?string
    {
        $value = $this->sourceQuery($prefix.'options')
            ->where('option_name', $optionName)
            ->value('option_value');

        return is_string($value) && $value !== '' ? $value : null;
    }

    private function rewriteContentHtml(string $html, ?string $homeUrl, ?string $siteUrl): string
    {
        $rewritten = $html;
        $prefixes = array_filter([
            $homeUrl !== null ? rtrim($homeUrl, '/').'/wp-content/uploads/' : null,
            $siteUrl !== null ? rtrim($siteUrl, '/').'/wp-content/uploads/' : null,
            '/wp-content/uploads/',
            'wp-content/uploads/',
        ]);

        foreach ($prefixes as $prefix) {
            $rewritten = str_replace($prefix, '/rescued-media/', $rewritten);
        }

        return $rewritten;
    }

    /**
     * @param  Collection<int, ContentItem>  $contentItems
     */
    private function importMenus(RescuedSite $site, string $prefix, Collection $contentItems): void
    {
        $menus = collect($this->sourceQuery($prefix.'term_taxonomy as tt')
            ->join($prefix.'terms as t', 't.term_id', '=', 'tt.term_id')
            ->where('tt.taxonomy', 'nav_menu')
            ->select(['t.term_id', 't.name', 't.slug'])
            ->get());

        if ($menus->isEmpty()) {
            return;
        }

        $menuAssignments = collect($this->sourceQuery($prefix.'term_relationships as tr')
            ->join($prefix.'term_taxonomy as tt', 'tt.term_taxonomy_id', '=', 'tr.term_taxonomy_id')
            ->where('tt.taxonomy', 'nav_menu')
            ->select(['tr.object_id', 'tt.term_id'])
            ->get())
            ->groupBy('term_id');

        $menuItemIds = $menuAssignments->flatten(1)->pluck('object_id')->map(fn ($id): int => (int) $id)->all();
        $menuPosts = collect($this->sourceQuery($prefix.'posts')
            ->whereIn('ID', $menuItemIds)
            ->where('post_type', 'nav_menu_item')
            ->get())
            ->keyBy('ID');
        $menuMeta = $this->groupPostMeta($prefix, $menuItemIds);

        foreach ($menus as $menuRow) {
            $menu = NavigationMenu::query()->create([
                'rescued_site_id' => $site->id,
                'original_term_id' => (int) $menuRow->term_id,
                'name' => (string) $menuRow->name,
                'slug' => (string) $menuRow->slug,
            ]);

            $sourceItems = collect($menuAssignments->get($menuRow->term_id, []))
                ->map(fn (object $item): object => $menuPosts->get($item->object_id))
                ->filter();

            foreach ($sourceItems as $sourceItem) {
                $meta = $menuMeta[(int) $sourceItem->ID] ?? [];
                $linkedSourceId = isset($meta['_menu_item_object_id'][0]) ? (int) $meta['_menu_item_object_id'][0] : null;
                $linkedContent = $linkedSourceId !== null ? $contentItems->get($linkedSourceId) : null;
                $label = trim((string) $sourceItem->post_title) !== '' ? (string) $sourceItem->post_title : ($linkedContent !== null ? $linkedContent->title : 'Menu item');
                $customUrl = $meta['_menu_item_url'][0] ?? null;

                NavigationItem::query()->create([
                    'navigation_menu_id' => $menu->id,
                    'original_source_id' => (int) $sourceItem->ID,
                    'source_parent_id' => isset($meta['_menu_item_menu_item_parent'][0]) ? (int) $meta['_menu_item_menu_item_parent'][0] : null,
                    'content_item_id' => $linkedContent?->id,
                    'label' => $label,
                    'url' => $customUrl !== null && $customUrl !== '' ? $customUrl : ($linkedContent !== null ? '/'.$linkedContent->path : null),
                    'position' => (int) $sourceItem->menu_order,
                ]);
            }

            /** @var Collection<int, NavigationItem> $itemMap */
            $itemMap = $menu->items()->get()->keyBy('original_source_id');

            foreach ($itemMap as $item) {
                if ($item->source_parent_id !== null) {
                    $item->forceFill([
                        'parent_id' => $itemMap->get($item->source_parent_id)?->id,
                    ])->save();
                }
            }
        }
    }

    private function augmentMenusWithPublishedPageTrees(RescuedSite $site): void
    {
        $menuId = NavigationMenu::query()
            ->where('rescued_site_id', $site->id)
            ->orderBy('id')
            ->value('id');

        if (! is_numeric($menuId)) {
            return;
        }

        $menu = NavigationMenu::query()->find((int) $menuId);

        if (! $menu instanceof NavigationMenu) {
            return;
        }

        $existingContentIds = NavigationItem::query()
            ->where('navigation_menu_id', $menu->id)
            ->pluck('content_item_id')
            ->filter()
            ->map(fn ($id): int => (int) $id)
            ->all();

        $candidatePageIds = ContentItem::query()
            ->where('rescued_site_id', $site->id)
            ->where('source_type', 'page')
            ->where('status', 'publish')
            ->whereNull('source_parent_id')
            ->whereNotIn('id', $existingContentIds)
            ->orderBy('menu_order')
            ->orderBy('published_at')
            ->pluck('id');

        foreach ($candidatePageIds as $candidatePageId) {
            $page = ContentItem::query()->find((int) $candidatePageId);

            if (! $page instanceof ContentItem) {
                continue;
            }

            $hasPublishedChildren = ContentItem::query()
                ->where('rescued_site_id', $site->id)
                ->where('source_type', 'page')
                ->where('status', 'publish')
                ->where('source_parent_id', $page->original_source_id)
                ->exists();

            if (! $hasPublishedChildren) {
                continue;
            }

            $parentItem = NavigationItem::query()->create([
                'navigation_menu_id' => $menu->id,
                'original_source_id' => 900000 + $page->original_source_id,
                'content_item_id' => $page->id,
                'label' => $page->title ?? $page->slug,
                'url' => '/'.$page->path,
                'position' => $this->nextMenuPosition($menu),
            ]);

            $this->appendPublishedPageChildren($menu, $parentItem, $site, $page->original_source_id);
        }
    }

    private function appendPublishedPageChildren(NavigationMenu $menu, NavigationItem $parentItem, RescuedSite $site, int $sourceParentId): void
    {
        $children = ContentItem::query()
            ->where('rescued_site_id', $site->id)
            ->where('source_type', 'page')
            ->where('status', 'publish')
            ->where('source_parent_id', $sourceParentId)
            ->orderBy('menu_order')
            ->orderBy('published_at')
            ->get();

        foreach ($children as $childPage) {
            $childItem = NavigationItem::query()->create([
                'navigation_menu_id' => $menu->id,
                'original_source_id' => 900000 + $childPage->original_source_id,
                'source_parent_id' => $parentItem->original_source_id,
                'parent_id' => $parentItem->id,
                'content_item_id' => $childPage->id,
                'label' => $childPage->title ?? $childPage->slug,
                'url' => '/'.$childPage->path,
                'position' => $this->nextMenuPosition($menu),
            ]);

            $this->appendPublishedPageChildren($menu, $childItem, $site, $childPage->original_source_id);
        }
    }

    private function nextMenuPosition(NavigationMenu $menu): int
    {
        return ((int) NavigationItem::query()
            ->where('navigation_menu_id', $menu->id)
            ->max('position')) + 1;
    }

    private function sourceQuery(string $table): QueryBuilder
    {
        return DB::connection($this->sourceConnection)->table($table);
    }

    private function resolveFrontPage(RescuedSite $site): void
    {
        if ($site->page_on_front_source_id === null) {
            return;
        }

        $currentFrontPage = ContentItem::query()
            ->where('rescued_site_id', $site->id)
            ->where('original_source_id', $site->page_on_front_source_id)
            ->first();

        if ($currentFrontPage === null || ! $this->shouldReplaceFrontPage($currentFrontPage)) {
            return;
        }

        $menuIds = NavigationMenu::query()
            ->where('rescued_site_id', $site->id)
            ->pluck('id');

        $menuItems = NavigationItem::query()
            ->whereIn('navigation_menu_id', $menuIds)
            ->whereNull('parent_id')
            ->whereNotNull('content_item_id')
            ->orderBy('position')
            ->get();

        $replacement = null;

        foreach ($menuItems as $menuItem) {
            $candidate = ContentItem::query()->find($menuItem->content_item_id);

            if (! $candidate instanceof ContentItem) {
                continue;
            }

            if ($candidate->status !== 'publish' || $candidate->source_type !== 'page') {
                continue;
            }

            if ($candidate->original_source_id === $currentFrontPage->original_source_id) {
                continue;
            }

            $replacement = $candidate;

            break;
        }

        if ($replacement === null) {
            return;
        }

        $site->forceFill([
            'page_on_front_source_id' => $replacement->original_source_id,
            'summary_json' => array_merge($site->summary_json ?? [], [
                'front_page_override' => [
                    'from_source_id' => $currentFrontPage->original_source_id,
                    'to_source_id' => $replacement->original_source_id,
                ],
            ]),
        ])->save();
    }

    private function shouldReplaceFrontPage(ContentItem $frontPage): bool
    {
        $haystack = Str::lower(trim(($frontPage->title ?? '').' '.strip_tags($frontPage->body_html ?? '')));

        return str_contains($haystack, 'lukket for behandlinger')
            || str_contains($haystack, 'holder lukket');
    }

    /**
     * @param  Collection<int, ContentItem>  $contentItems
     */
    private function createLegacyQueryRedirects(RescuedSite $site, Collection $contentItems): void
    {
        foreach ($contentItems as $contentItem) {
            RedirectRule::query()->create([
                'rescued_site_id' => $site->id,
                'from_path' => '/?p='.$contentItem->original_source_id,
                'to_path' => '/'.$contentItem->path,
            ]);
        }
    }

    private function toNullableInt(?string $value): ?int
    {
        if ($value === null || $value === '' || ! is_numeric($value)) {
            return null;
        }

        return (int) $value;
    }
}
