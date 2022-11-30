<?php

declare(strict_types=1);

namespace App\Models\Tickets\Ordering\InfoForOrder\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;
use Tickets\Shared\Infrastructure\Models\HasUuid;

/**
 * App\Models\Tickets\Ordering\InfoForOrder\Models\TicketTypes
 *
 * @property string $id
 * @property string $name
 * @property float $price
 * @property int|null $groupLimit
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @method static Builder|TicketTypes newModelQuery()
 * @method static Builder|TicketTypes newQuery()
 * @method static Builder|TicketTypes query()
 * @method static Builder|TicketTypes whereCreatedAt($value)
 * @method static Builder|TicketTypes whereGroupLimit($value)
 * @method static Builder|TicketTypes whereId($value)
 * @method static Builder|TicketTypes whereName($value)
 * @method static Builder|TicketTypes wherePrice($value)
 * @method static Builder|TicketTypes whereUpdatedAt($value)
 */
class TicketTypes extends Model
{
    use HasFactory, HasUuid;

    public const TABLE = 'ticket_type';

    protected $table = self::TABLE;
}
