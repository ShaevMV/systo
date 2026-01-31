<?php

declare(strict_types=1);

namespace App\Models\Invite;

use App\Models\Ordering\CommentOrderTicketModel;
use Eloquent;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;
use Shared\Infrastructure\Models\HasUuid;


/**
 * App\Models\Invite\InviteModel
 *
 * @property string $id
 * @property mixed $order_id_list
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @method static Builder|InviteModel newModelQuery()
 * @method static Builder|InviteModel newQuery()
 * @method static Builder|InviteModel query()
 * @method static Builder|InviteModel whereCreatedAt($value)
 * @method static Builder|InviteModel whereId($value)
 * @method static Builder|InviteModel whereOrderIdList($value)
 * @method static Builder|InviteModel whereUpdatedAt($value)
 * @mixin Eloquent
 */
final class InviteModel extends Model
{
    use HasFactory, HasUuid;
    public const TABLE = 'invite';
    protected $table = self::TABLE;

    protected $fillable = [
        'id', 'order_id_list'
    ];
}
