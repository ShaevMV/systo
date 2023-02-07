<?php

declare(strict_types=1);

namespace App\Models\Ordering;

use App\Models\Ordering\InfoForOrder\TicketTypesModel;
use App\Models\Ordering\InfoForOrder\TypesOfPaymentModel;
use App\Models\Tickets\TicketModel;
use App\Models\User;
use Eloquent;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
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
 * @mixin Eloquent
 * @property-read Collection|CommentOrderTicketModel[] $comments
 * @property-read int|null $comments_count
 * @property-read TicketTypesModel $ticketType
 * @property-read TypesOfPaymentModel $typeOfPayment
 * @property string $festival_id
 * @property string $id_buy
 * @property string $phone
 * @property-read User $users
 * @method static Builder|OrderTicketModel whereFestivalId($value)
 * @method static Builder|OrderTicketModel whereIdBuy($value)
 * @method static Builder|OrderTicketModel wherePhone($value)
 * @property int $kilter
 * @method static Builder|OrderTicketModel whereKilter($value)
 */
final class OrderTicketModel extends Model
{
    use HasFactory, HasUuid;

    public const TABLE = 'order_tickets';
    protected $table = self::TABLE;

    protected $fillable = [
        'id', 'guests', 'user_id', 'ticket_type_id', 'promo_code', 'types_of_payment_id', 'price', 'discount', 'status',
        'date', 'phone',
    ];

    public function comments(): HasMany
    {
        return $this->hasMany(CommentOrderTicketModel::class, 'order_tickets_id');
    }

    public function users(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function tickets(): hasMany
    {
        return $this->hasMany(TicketModel::class, 'order_ticket_id');
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
