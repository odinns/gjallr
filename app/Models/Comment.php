<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property int $content_item_id
 * @property int $original_source_id
 * @property int|null $source_parent_id
 * @property int|null $parent_id
 * @property string|null $author_name
 * @property string|null $author_email
 * @property string $body
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property Collection<int, Comment> $children
 */
class Comment extends Model
{
    public $timestamps = false;

    protected $fillable = [
        'content_item_id',
        'original_source_id',
        'source_parent_id',
        'parent_id',
        'author_name',
        'author_email',
        'body',
        'created_at',
        'updated_at',
    ];

    protected function casts(): array
    {
        return [
            'created_at' => 'datetime',
            'updated_at' => 'datetime',
        ];
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
