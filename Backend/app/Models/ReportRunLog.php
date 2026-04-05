<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Shared\Infrastructure\Models\HasUuid;

class ReportRunLog extends Model
{
    use HasUuid;

    protected $table = 'report_run_logs';

    protected $fillable = [
        'report_config_id',
        'started_at',
        'finished_at',
        'exported_rows',
        'status',
        'error_message',
    ];

    protected $casts = [
        'started_at' => 'datetime',
        'finished_at' => 'datetime',
        'exported_rows' => 'integer',
    ];

    public function config(): BelongsTo
    {
        return $this->belongsTo(ReportConfig::class, 'report_config_id');
    }
}
