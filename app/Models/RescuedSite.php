<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * @property int $id
 * @property int|null $import_run_id
 * @property string|null $source_label
 * @property string|null $name
 * @property string|null $site_url
 * @property string|null $home_url
 * @property string|null $permalink_structure
 * @property string|null $active_theme
 * @property string|null $source_prefix
 * @property string $show_on_front
 * @property int|null $page_on_front_source_id
 * @property int|null $page_for_posts_source_id
 * @property string|null $site_path
 * @property array<string, mixed>|null $summary_json
 */
class RescuedSite extends Model
{
    protected $fillable = [
        'import_run_id',
        'source_label',
        'name',
        'site_url',
        'home_url',
        'permalink_structure',
        'active_theme',
        'source_prefix',
        'show_on_front',
        'page_on_front_source_id',
        'page_for_posts_source_id',
        'site_path',
        'summary_json',
    ];

    protected function casts(): array
    {
        return [
            'summary_json' => 'array',
        ];
    }

    public function importRun(): BelongsTo
    {
        return $this->belongsTo(ImportRun::class);
    }

    public function contentItems(): HasMany
    {
        return $this->hasMany(ContentItem::class);
    }

    public function navigationMenus(): HasMany
    {
        return $this->hasMany(NavigationMenu::class);
    }

    public function taxonomies(): HasMany
    {
        return $this->hasMany(Taxonomy::class);
    }
}
