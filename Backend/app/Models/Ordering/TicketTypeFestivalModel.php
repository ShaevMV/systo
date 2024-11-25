<?php

declare(strict_types=1);

namespace App\Models\Ordering;

use Illuminate\Database\Eloquent\Model;

final class TicketTypeFestivalModel  extends Model
{
    public const TABLE = 'ticket_type_festival';
    protected $table = self::TABLE;
}
