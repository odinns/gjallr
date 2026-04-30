<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * @property int $id
 * @property int $rescued_site_id
 * @property int $original_term_id
 * @property string $name
 * @property string $slug
 * @property Collection<int, NavigationItem> $items
 */
class NavigationMenu extends Model
{
    protected $fillable = [
        'rescued_site_id',
        'original_term_id',
        'name',
        'slug',
    ];

    public function rescuedSite(): BelongsTo
    {
        return $this->belongsTo(RescuedSite::class);
    }

    public function items(): HasMany
    {
        return $this->hasMany(NavigationItem::class);
    }
}
