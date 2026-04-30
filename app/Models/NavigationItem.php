<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * @property int $id
 * @property int $navigation_menu_id
 * @property int $original_source_id
 * @property int|null $source_parent_id
 * @property int|null $parent_id
 * @property int|null $content_item_id
 * @property string $label
 * @property string|null $url
 * @property int $position
 * @property ContentItem|null $contentItem
 * @property Collection<int, NavigationItem> $children
 */
class NavigationItem extends Model
{
    protected $fillable = [
        'navigation_menu_id',
        'original_source_id',
        'source_parent_id',
        'parent_id',
        'content_item_id',
        'label',
        'url',
        'position',
    ];

    public function navigationMenu(): BelongsTo
    {
        return $this->belongsTo(NavigationMenu::class);
    }

    public function contentItem(): BelongsTo
    {
        return $this->belongsTo(ContentItem::class);
    }

    public function parent(): BelongsTo
    {
        return $this->belongsTo(self::class, 'parent_id');
    }

    public function children(): HasMany
    {
        return $this->hasMany(self::class, 'parent_id');
    }
}
