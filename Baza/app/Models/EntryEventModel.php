<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * Запись append-only журнала проходов (Ф5, PR-8). См. миграцию baza_entry_events.
 *
 * @property int $id
 * @property string $client_op_id
 * @property string $type
 * @property int|null $kilter
 * @property string|null $ticket_uuid
 * @property string|null $device_id
 * @property int|null $change_id
 * @property bool $is_duplicate
 */
class EntryEventModel extends Model
{
    protected $table = self::TABLE;

    public const TABLE = 'baza_entry_events';

    protected $fillable = [
        'client_op_id',
        'type',
        'kilter',
        'ticket_uuid',
        'device_id',
        'change_id',
        'entered_at',
        'is_duplicate',
        'festival_id',
        'nonce',
    ];

    protected $casts = [
        'kilter' => 'integer',
        'change_id' => 'integer',
        'is_duplicate' => 'boolean',
        'entered_at' => 'datetime',
    ];
}
