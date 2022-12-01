<?php

declare(strict_types=1);

namespace App\Models\Tickets\Ordering;

use Eloquent;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
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
 * @method static Builder|CommentOrderTicket newModelQuery()
 * @method static Builder|CommentOrderTicket newQuery()
 * @method static Builder|CommentOrderTicket query()
 * @method static Builder|CommentOrderTicket whereComment($value)
 * @method static Builder|CommentOrderTicket whereCreatedAt($value)
 * @method static Builder|CommentOrderTicket whereId($value)
 * @method static Builder|CommentOrderTicket whereIsCheckin($value)
 * @method static Builder|CommentOrderTicket whereOrderTicketsId($value)
 * @method static Builder|CommentOrderTicket whereUpdatedAt($value)
 * @method static Builder|CommentOrderTicket whereUserId($value)
 * @mixin Eloquent
 */
final class CommentOrderTicket extends Model
{
    use HasFactory, HasUuid;
    public const TABLE = 'comment';
    protected $table = self::TABLE;

    protected $fillable = [
        'id', 'user_id', 'order_tickets_id', 'comment', 'is_checkin'
    ];
}
