<?php

declare(strict_types=1);

namespace App\Models\Tickets\Ordering;

use App\Models\Tickets\Ordering\InfoForOrder\TicketTypesModel;
use App\Models\Tickets\Ordering\InfoForOrder\TypesOfPaymentModel;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
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
 * @method static Builder|OrderTicketModel newModelQuery()
 * @method static Builder|OrderTicketModel newQuery()
 * @method static Builder|OrderTicketModel query()
 * @method static Builder|OrderTicketModel whereCreatedAt($value)
 * @method static Builder|OrderTicketModel whereGuests($value)
 * @method static Builder|OrderTicketModel whereId($value)
 * @method static Builder|OrderTicketModel wherePromoCodeId($value)
 * @method static Builder|OrderTicketModel whereTicketTypeId($value)
 * @method static Builder|OrderTicketModel whereTypesOfPaymentId($value)
 * @method static Builder|OrderTicketModel whereUpdatedAt($value)
 * @method static Builder|OrderTicketModel whereUserId($value)
 * @method static create(array $toArray)
 * @property float $price
 * @property float $discount
 * @property string $status
 * @property string $date
 * @method static Builder|OrderTicketModel whereDate($value)
 * @method static Builder|OrderTicketModel whereDiscount($value)
 * @method static Builder|OrderTicketModel wherePrice($value)
 * @method static Builder|OrderTicketModel wherePromoCode($value)
 * @method static Builder|OrderTicketModel whereStatus($value)
 * @mixin \Eloquent
 */
final class OrderTicketModel extends Model
{
    use HasFactory, HasUuid;

    public const TABLE = 'order_tickets';
    protected $table = self::TABLE;

    protected $fillable = [
        'id', 'guests', 'user_id', 'ticket_type_id', 'promo_code', 'types_of_payment_id', 'price', 'discount', 'status',
        'date'
    ];

    public function comments(): HasMany
    {
        return $this->hasMany(CommentOrderTicketModel::class, 'order_tickets_id');
    }

    public function ticketType(): BelongsTo
    {
        return $this->belongsTo(TicketTypesModel::class, 'ticket_type_id');
    }

    public function typeOfPayment(): BelongsTo
    {
        return $this->belongsTo(TypesOfPaymentModel::class, 'types_of_payment_id');
    }
}