<?php

declare(strict_types=1);

namespace App\Models\Tickets\Ordering;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

use Illuminate\Support\Carbon;
use Tickets\Shared\Infrastructure\Models\HasUuid;

/**
 * App\Models\Tickets\Ordering\OrderTicket
 *
 * @property string $id
 * @property mixed $guests
 * @property string $user_id
 * @property string $ticket_type_id
 * @property string $promo_code
 * @property string $types_of_payment_id
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @method static Builder|OrderTicket newModelQuery()
 * @method static Builder|OrderTicket newQuery()
 * @method static Builder|OrderTicket query()
 * @method static Builder|OrderTicket whereCreatedAt($value)
 * @method static Builder|OrderTicket whereGuests($value)
 * @method static Builder|OrderTicket whereId($value)
 * @method static Builder|OrderTicket wherePromoCodeId($value)
 * @method static Builder|OrderTicket whereTicketTypeId($value)
 * @method static Builder|OrderTicket whereTypesOfPaymentId($value)
 * @method static Builder|OrderTicket whereUpdatedAt($value)
 * @method static Builder|OrderTicket whereUserId($value)
 * @method static create(array $toArray)
 */
final class OrderTicket extends Model
{
    use HasFactory, HasUuid;
    public const TABLE = 'order_tickets';
    protected $table = self::TABLE;

    protected $fillable = [
        'id', 'guests', 'user_id', 'ticket_type_id', 'promo_code', 'types_of_payment_id', 'price', 'discount', 'status', 'date'
    ];
}
