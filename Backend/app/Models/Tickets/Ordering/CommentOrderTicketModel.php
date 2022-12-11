<?php

declare(strict_types=1);

namespace App\Models\Tickets\Ordering;

use Eloquent;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;
use Tickets\Shared\Infrastructure\Models\HasUuid;

/**
 * App\Models\Tickets\Ordering\CommentOrderTicket
 *
 * @property string $id
 * @property string $user_id
 * @property string $order_tickets_id
 * @property string $comment
 * @property int $is_checkin
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @method static Builder|CommentOrderTicketModel newModelQuery()
 * @method static Builder|CommentOrderTicketModel newQuery()
 * @method static Builder|CommentOrderTicketModel query()
 * @method static Builder|CommentOrderTicketModel whereComment($value)
 * @method static Builder|CommentOrderTicketModel whereCreatedAt($value)
 * @method static Builder|CommentOrderTicketModel whereId($value)
 * @method static Builder|CommentOrderTicketModel whereIsCheckin($value)
 * @method static Builder|CommentOrderTicketModel whereOrderTicketsId($value)
 * @method static Builder|CommentOrderTicketModel whereUpdatedAt($value)
 * @method static Builder|CommentOrderTicketModel whereUserId($value)
 * @mixin Eloquent
 * @property-read OrderTicketModel|null $order
 */
final class CommentOrderTicketModel extends Model
{
    use HasFactory, HasUuid;
    public const TABLE = 'comment';
    protected $table = self::TABLE;

    protected $fillable = [
        'id', 'user_id', 'order_tickets_id', 'comment', 'is_checkin'
    ];

    public function order(): BelongsTo
    {
        return $this->belongsTo(OrderTicketModel::class);
    }
}
