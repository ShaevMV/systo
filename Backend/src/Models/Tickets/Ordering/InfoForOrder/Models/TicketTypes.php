<?php

declare(strict_types = 1);

namespace App\Models\Tickets\Ordering\InfoForOrder\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Tickets\Shared\Infrastructure\Models\HasUuid;

/**
 * App\Models\Tickets\Ordering\InfoForOrder\Models\TicketTypes
 *
 * @property string $id
 * @property string $name
 * @property float $price
 * @property int|null $groupLimit
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @method static \Illuminate\Database\Eloquent\Builder|TicketTypes newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|TicketTypes newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|TicketTypes query()
 * @method static \Illuminate\Database\Eloquent\Builder|TicketTypes whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|TicketTypes whereGroupLimit($value)
 * @method static \Illuminate\Database\Eloquent\Builder|TicketTypes whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|TicketTypes whereName($value)
 * @method static \Illuminate\Database\Eloquent\Builder|TicketTypes wherePrice($value)
 * @method static \Illuminate\Database\Eloquent\Builder|TicketTypes whereUpdatedAt($value)
 * @mixin \Eloquent
 */
class TicketTypes extends Model
{
    use HasFactory, HasUuid;

    protected $table = 'ticket_type';
}
