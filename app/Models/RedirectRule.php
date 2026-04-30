<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property int $id
 * @property int $rescued_site_id
 * @property string $from_path
 * @property string $to_path
 */
class RedirectRule extends Model
{
    protected $fillable = [
        'rescued_site_id',
        'from_path',
        'to_path',
    ];

    public function rescuedSite(): BelongsTo
    {
        return $this->belongsTo(RescuedSite::class);
    }
}
