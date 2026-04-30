<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

/**
 * @property int $id
 * @property int $rescued_site_id
 * @property int $original_term_taxonomy_id
 * @property int $original_term_id
 * @property string $type
 * @property string $name
 * @property string $slug
 * @property string $path
 * @property string|null $description
 * @property int|null $source_parent_term_id
 * @property Collection<int, ContentItem> $contentItems
 */
class Taxonomy extends Model
{
    protected $fillable = [
        'rescued_site_id',
        'original_term_taxonomy_id',
        'original_term_id',
        'type',
        'name',
        'slug',
        'path',
        'description',
        'source_parent_term_id',
    ];

    public function rescuedSite(): BelongsTo
    {
        return $this->belongsTo(RescuedSite::class);
    }

    public function contentItems(): BelongsToMany
    {
        return $this->belongsToMany(ContentItem::class, 'content_taxonomy');
    }
}
