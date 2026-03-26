<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Shared\Infrastructure\Models\HasUuid;

class ReportConfig extends Model
{
    use HasUuid;

    protected $table = 'report_configs';

    protected $fillable = [
        'name',
        'report_type',
        'spreadsheet_id',
        'sheet_name',
        'start_row',
        'filters',
        'is_active',
        'cron_expression',
        'timezone',
        'last_run_at',
        'last_run_status',
        'last_run_message',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'start_row' => 'integer',
        'filters' => 'array',
        'last_run_at' => 'datetime',
    ];

    public function logs(): HasMany
    {
        return $this->hasMany(ReportRunLog::class, 'report_config_id');
    }

    public function updateLastRun(string $status, int $rows, ?string $message = null): void
    {
        $this->update([
            'last_run_at' => now(),
            'last_run_status' => $status,
            'last_run_message' => $message,
        ]);
    }
}
