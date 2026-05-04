<?php

declare(strict_types=1);

namespace App\Models\Ordering;

use App\Models\Festival\TicketTypesModel;
use App\Models\Festival\TypesOfPaymentModel;
use App\Models\Tickets\TicketModel;
use App\Models\User;
use Eloquent;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;
use Shared\Domain\ValueObject\Uuid;
use Shared\Infrastructure\Models\HasUuid;

/**
 * App\Models\Ordering\OrderTicketModel
 *
 * @property int $kilter
 * @property string $id
 * @property mixed $guests
 * @property string $festival_id
 * @property string $user_id
 * @property string $ticket_type_id
 * @property string|null $promo_code
 * @property string $id_buy
 * @property string $phone
 * @property string $types_of_payment_id
 * @property float $price
 * @property float $discount
 * @property string $status
 * @property string $date
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property-read Collection|\App\Models\Ordering\CommentOrderTicketModel[] $comments
 * @property-read int|null $comments_count
 * @property-read TicketTypesModel|null $ticketType
 * @property-read Collection|TicketModel[] $tickets
 * @property-read int|null $tickets_count
 * @property-read TypesOfPaymentModel|null $typeOfPayment
 * @property-read User|null $users
 * @method static Builder|OrderTicketModel newModelQuery()
 * @method static Builder|OrderTicketModel newQuery()
 * @method static Builder|OrderTicketModel query()
 * @method static Builder|OrderTicketModel whereCreatedAt($value)
 * @method static Builder|OrderTicketModel whereDate($value)
 * @method static Builder|OrderTicketModel whereDiscount($value)
 * @method static Builder|OrderTicketModel whereFestivalId($value)
 * @method static Builder|OrderTicketModel whereGuests($value)
 * @method static Builder|OrderTicketModel whereId($value)
 * @method static Builder|OrderTicketModel whereIdBuy($value)
 * @method static Builder|OrderTicketModel whereKilter($value)
 * @method static Builder|OrderTicketModel wherePhone($value)
 * @method static Builder|OrderTicketModel wherePrice($value)
 * @method static Builder|OrderTicketModel wherePromoCode($value)
 * @method static Builder|OrderTicketModel whereStatus($value)
 * @method static Builder|OrderTicketModel whereTicketTypeId($value)
 * @method static Builder|OrderTicketModel whereTypesOfPaymentId($value)
 * @method static Builder|OrderTicketModel whereUpdatedAt($value)
 * @method static Builder|OrderTicketModel whereUserId($value)
 * @mixin Eloquent
 * @property string $type
 * @method static Builder|OrderTicketModel whereType($value)
 * @property string|null $friendly_id
 * @method static Builder|OrderTicketModel whereFriendlyId($value)
 */
final class OrderTicketModel extends Model
{
    use HasFactory, HasUuid;

    public const TABLE = 'order_tickets';
    protected $table = self::TABLE;

    protected $fillable = [
        'id', 'guests', 'user_id', 'ticket_type_id', 'promo_code', 'types_of_payment_id', 'price', 'discount', 'status',
        'date', 'phone', 'friendly_id', 'location_id', 'curator_id',
    ];

    public function location(): BelongsTo
    {
        return $this->belongsTo(\App\Models\Location\LocationModel::class, 'location_id');
    }

    public function curator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'curator_id');
    }

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

    public function festivalsUuid(): array
    {
        $ticketType = $this->ticketType()
            ->with([
                'festival'
            ])
            ->first()
            ->toArray();

        return array_map(fn(array $data) => new Uuid($data['id']), $ticketType['festival']);
    }
}
