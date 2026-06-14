<?php

declare(strict_types=1);

namespace App\Models\Integration;

use Illuminate\Database\Eloquent\Model;

/**
 * Запись о уже обработанном входящем событии (дедупликация qr → org).
 * См. .claude/specs/qr-integration/CONTRACT_RFC_v0.md §8.
 */
final class ProcessedMessageModel extends Model
{
    public const TABLE = 'processed_messages';

    protected $table = self::TABLE;
    public $timestamps = false;

    protected $fillable = [
        'idempotency_key',
        'event_type',
        'source',
        'trace_id',
        'processed_at',
    ];

    protected $casts = [
        'processed_at' => 'datetime',
    ];
}
