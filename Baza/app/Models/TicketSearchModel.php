<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * Поисковый индекс билетов (ручной поиск на КПП без QR). См. миграцию ticket_search.
 *
 * @property int $id
 * @property string $ticket_uuid
 * @property string|null $festival_id
 * @property string $type
 * @property int|null $kilter
 * @property array|null $payload
 */
class TicketSearchModel extends Model
{
    protected $table = self::TABLE;

    public const TABLE = 'ticket_search';

    protected $fillable = [
        'ticket_uuid',
        'festival_id',
        'type',
        'kilter',
        'fio',
        'phone',
        'telegram',
        'email',
        'city',
        'car_number',
        'child_name',
        'parent_phone',
        'external_order_no',
        'type_ticket',
        'payload',
    ];

    protected $casts = [
        'payload' => 'array',
    ];
}
