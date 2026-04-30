<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property int|null $source_analysis_run_id
 * @property string|null $source_label
 * @property string $status
 * @property string|null $sql_dump_path
 * @property string|null $site_path
 * @property string|null $detected_prefix
 * @property array<string, mixed>|null $summary_json
 * @property string|null $notes
 * @property Carbon|null $imported_at
 */
class ImportRun extends Model
{
    protected $fillable = [
        'source_analysis_run_id',
        'source_label',
        'status',
        'sql_dump_path',
        'site_path',
        'detected_prefix',
        'summary_json',
        'notes',
        'imported_at',
    ];

    protected function casts(): array
    {
        return [
            'summary_json' => 'array',
            'imported_at' => 'datetime',
        ];
    }

    public function sourceAnalysisRun(): BelongsTo
    {
        return $this->belongsTo(SourceAnalysisRun::class);
    }
}
