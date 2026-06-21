<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * Отозванный билет (Ф5, PR-6, реш. B6). Без ПДн. См. миграцию baza_blacklist.
 *
 * @property int $id
 * @property string|null $ticket_uuid
 * @property int|null $kilter
 * @property string|null $festival_id
 * @property string|null $reason
 */
class BlacklistModel extends Model
{
    protected $table = self::TABLE;

    public const TABLE = 'baza_blacklist';

    protected $fillable = [
        'ticket_uuid',
        'kilter',
        'festival_id',
        'reason',
    ];

    protected $casts = [
        'kilter' => 'integer',
    ];
}
