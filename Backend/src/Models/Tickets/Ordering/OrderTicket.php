<?php

declare(strict_types = 1);

namespace App\Models\Tickets\Ordering;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

use Tickets\Shared\Infrastructure\Models\HasUuid;

/**
 * App\Models\Tickets\Ordering\OrderTicket
 *
 * @property string $id
 * @property mixed $guests
 * @property string $user_id
 * @property string $ticket_type_id
 * @property string $promo_code_id
 * @property string $types_of_payment_id
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @method static \Illuminate\Database\Eloquent\Builder|OrderTicket newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|OrderTicket newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|OrderTicket query()
 * @method static \Illuminate\Database\Eloquent\Builder|OrderTicket whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|OrderTicket whereGuests($value)
 * @method static \Illuminate\Database\Eloquent\Builder|OrderTicket whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|OrderTicket wherePromoCodeId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|OrderTicket whereTicketTypeId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|OrderTicket whereTypesOfPaymentId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|OrderTicket whereUpdatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|OrderTicket whereUserId($value)
 * @mixin \Eloquent
 */
final class OrderTicket extends Model
{
    use HasFactory, HasUuid;

    protected $table = 'order_tickets';
}
