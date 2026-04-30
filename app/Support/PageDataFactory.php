<?php

declare(strict_types=1);

namespace App\Support;

use App\Models\Comment;
use App\Models\ContentItem;
use App\Models\MediaAsset;
use App\Models\NavigationItem;
use App\Models\NavigationMenu;
use App\Models\RescuedSite;
use App\Models\Taxonomy;
use Illuminate\Database\Eloquent\Collection as EloquentCollection;

final class PageDataFactory
{
    /**
     * @param  EloquentCollection<int, NavigationMenu>  $menus
     * @return array<string, mixed>
     */
    public function forContentItem(ContentItem $contentItem, RescuedSite $site, EloquentCollection $menus): array
    {
        $contentItem->loadMissing(['taxonomies', 'mediaAssets', 'comments.children']);

        return [
            'site' => [
                'name' => $site->name,
                'home_url' => $site->home_url,
                'active_theme' => $site->active_theme,
            ],
            'page' => [
                'title' => $contentItem->title,
                'excerpt' => $contentItem->excerpt,
                'body_html' => $contentItem->body_html,
                'seo_title' => $contentItem->seo_title,
                'seo_description' => $contentItem->seo_description,
                'path' => '/'.$contentItem->path,
                'source_type' => $contentItem->source_type,
                'status' => $contentItem->status,
                'published_at' => $contentItem->published_at?->toIso8601String(),
                'taxonomies' => $contentItem->taxonomies->map(fn (Taxonomy $taxonomy): array => [
                    'type' => $taxonomy->type,
                    'name' => $taxonomy->name,
                    'slug' => $taxonomy->slug,
                    'path' => '/'.$taxonomy->path,
                ])->all(),
                'media' => $contentItem->mediaAssets->map(fn (MediaAsset $media): array => [
                    'url' => $media->url,
                    'path' => $media->path,
                    'mime_type' => $media->mime_type,
                    'alt_text' => $media->alt_text,
                ])->all(),
                'comments' => $contentItem->comments
                    ->whereNull('parent_id')
                    ->sortBy('created_at')
                    ->map(fn (Comment $comment): array => [
                        'author_name' => $comment->author_name,
                        'body' => $comment->body,
                        'created_at' => $comment->created_at?->toIso8601String(),
                        'children' => $comment->children->map(fn (Comment $child): array => [
                            'author_name' => $child->author_name,
                            'body' => $child->body,
                            'created_at' => $child->created_at?->toIso8601String(),
                        ])->values()->all(),
                    ])->values()->all(),
            ],
            'menus' => $menus->map(fn (NavigationMenu $menu): array => [
                'name' => $menu->name,
                'items' => $menu->items
                    ->whereNull('parent_id')
                    ->filter(fn (NavigationItem $item): bool => $item->url !== null && ($item->contentItem === null || $item->contentItem->status === 'publish'))
                    ->map(fn (NavigationItem $item): array => [
                        'label' => $item->label,
                        'url' => $item->url,
                        'children' => $item->children
                            ->filter(fn (NavigationItem $child): bool => $child->url !== null && ($child->contentItem === null || $child->contentItem->status === 'publish'))
                            ->map(fn (NavigationItem $child): array => [
                                'label' => $child->label,
                                'url' => $child->url,
                            ])->values()->all(),
                    ])->values()->all(),
            ])->all(),
        ];
    }
}
