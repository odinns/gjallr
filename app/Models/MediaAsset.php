<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property int $id
 * @property int $rescued_site_id
 * @property int $original_source_id
 * @property int|null $content_item_id
 * @property string|null $path
 * @property string|null $url
 * @property string|null $mime_type
 * @property string|null $alt_text
 */
class MediaAsset extends Model
{
    protected $fillable = [
        'rescued_site_id',
        'original_source_id',
        'content_item_id',
        'path',
        'url',
        'mime_type',
        'alt_text',
    ];

    public function rescuedSite(): BelongsTo
    {
        return $this->belongsTo(RescuedSite::class);
    }

    public function contentItem(): BelongsTo
    {
        return $this->belongsTo(ContentItem::class);
    }
}
