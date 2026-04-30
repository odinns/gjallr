<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property int $rescued_site_id
 * @property string $source_type
 * @property int $original_source_id
 * @property string|null $title
 * @property string $slug
 * @property string $path
 * @property string|null $excerpt
 * @property string|null $body_html
 * @property string|null $status
 * @property Carbon|null $published_at
 * @property int|null $source_parent_id
 * @property int $menu_order
 * @property string|null $seo_title
 * @property string|null $seo_description
 * @property bool $is_posts_index
 * @property Collection<int, Taxonomy> $taxonomies
 * @property Collection<int, Comment> $comments
 * @property Collection<int, MediaAsset> $mediaAssets
 */
class ContentItem extends Model
{
    protected $fillable = [
        'rescued_site_id',
        'source_type',
        'original_source_id',
        'title',
        'slug',
        'path',
        'excerpt',
        'body_html',
        'status',
        'published_at',
        'source_parent_id',
        'menu_order',
        'seo_title',
        'seo_description',
        'is_posts_index',
    ];

    protected function casts(): array
    {
        return [
            'published_at' => 'datetime',
            'is_posts_index' => 'bool',
        ];
    }

    public function rescuedSite(): BelongsTo
    {
        return $this->belongsTo(RescuedSite::class);
    }

    public function taxonomies(): BelongsToMany
    {
        return $this->belongsToMany(Taxonomy::class, 'content_taxonomy');
    }

    public function comments(): HasMany
    {
        return $this->hasMany(Comment::class);
    }

    public function mediaAssets(): HasMany
    {
        return $this->hasMany(MediaAsset::class);
    }

    public function scopePubliclyVisible(Builder $query): Builder
    {
        return $query->where('status', 'publish');
    }
}
