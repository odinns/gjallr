<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property string $source_type
 * @property string|null $source_label
 * @property string|null $sql_dump_path
 * @property string|null $site_path
 * @property string|null $detected_prefix
 * @property string|null $detected_version
 * @property string|null $detected_db_version
 * @property string $compatibility_band
 * @property bool $has_uploads
 * @property int $tables_count
 * @property int $plugins_count
 * @property int $themes_count
 * @property int $suspicious_findings_count
 * @property string $artifact_path
 * @property array<string, mixed>|null $summary_json
 * @property Carbon $analyzed_at
 */
class SourceAnalysisRun extends Model
{
    protected $fillable = [
        'source_type',
        'source_label',
        'sql_dump_path',
        'site_path',
        'detected_prefix',
        'detected_version',
        'detected_db_version',
        'compatibility_band',
        'has_uploads',
        'tables_count',
        'plugins_count',
        'themes_count',
        'suspicious_findings_count',
        'artifact_path',
        'summary_json',
        'analyzed_at',
    ];

    protected function casts(): array
    {
        return [
            'has_uploads' => 'bool',
            'summary_json' => 'array',
            'analyzed_at' => 'datetime',
        ];
    }
}
